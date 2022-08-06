<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Maker;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\StringExcelCell;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidAnnotationParamException;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidDisplayModelSetterParameterTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\ModelPropertyNotSettableException;
use Kczer\ExcelImporterBundle\Exception\Annotation\NotExistingModelClassException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedAnnotationOptionException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedColumnExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionExpectedDataTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionValueDataTypeException;
use Kczer\ExcelImporterBundle\Exception\DuplicateExcelIdentifierException;
use Kczer\ExcelImporterBundle\Model\AbstractDisplayModel;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\Factory\ModelPropertyMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Kczer\ExcelImporterBundle\Model\ModelPropertyMetadata;
use Kczer\ExcelImporterBundle\Twig\Twig;
use Kczer\ExcelImporterBundle\Util\CommandHelper;
use Kczer\ExcelImporterBundle\Util\FieldIdResolver;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function array_diff_key;
use function array_filter;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function get_class;
use function key_exists;
use function max;
use function preg_match;
use function preg_replace;
use function sort;
use function strtoupper;
use function trim;
use function uksort;
use const DIRECTORY_SEPARATOR;

class ModelMaker
{
    public const MODEL_NAMESPACE = 'App\\Model\\ExcelImporter\\';

    /** @var array<class-string<AbstractExcelCell>, AbstractExcelCell[]> */
    private $excelCells = [];

    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var ModelPropertyMetadataFactory */
    private $modelPropertyMetadataFactory;

    /** @var CommandHelper */
    private $commandHelper;

    /** @var Twig */
    private $twig;

    /** @var bool */
    private $emptyBoolAsFalse;

    /** @var FieldIdResolver */
    private $fieldIdResolver;

    /** @var string */
    private $projectDir;


    /** @var Filesystem */
    private $filesystem;


    public function __construct(
        ModelMetadataFactory         $modelMetadataFactory,
        ModelPropertyMetadataFactory $modelPropertyMetadataFactory,
        CommandHelper                $questionHelper,
        Twig                         $twig,
        FieldIdResolver              $fieldIdResolver,
        bool                         $emptyBoolAsFalse,
        string                       $projectDir
    ) {
        $this->modelMetadataFactory = $modelMetadataFactory;
        $this->modelPropertyMetadataFactory = $modelPropertyMetadataFactory;
        $this->commandHelper = $questionHelper;
        $this->twig = $twig;
        $this->fieldIdResolver = $fieldIdResolver;
        $this->emptyBoolAsFalse = $emptyBoolAsFalse;
        $this->projectDir = $projectDir;

        $this->filesystem = new Filesystem();
    }

    /**
     * Method used By Kczer\ExcelImporterBundle\DependencyInjection\Compiler\ExcelCellRegistrationPass::process()
     */
    public function addExcelCell(AbstractExcelCell $excelCell): void
    {
        $this->excelCells[get_class($excelCell)] = $excelCell;
    }

    public function setCommandInOut(
        InputInterface  $input,
        OutputInterface $output
    ): self
    {
        $this->commandHelper->setInOut($input, $output);

        return $this;
    }

    /**
     * @throws DuplicateExcelIdentifierException
     * @throws InvalidAnnotationParamException
     * @throws InvalidDisplayModelSetterParameterTypeException
     * @throws ModelPropertyNotSettableException
     * @throws NotExistingModelClassException
     * @throws ReflectionException
     * @throws UnexpectedAnnotationOptionException
     * @throws UnexpectedColumnExcelCellClassException
     * @throws UnexpectedOptionExpectedDataTypeException
     * @throws UnexpectedOptionValueDataTypeException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws Exception
     */
    public function makeModelClasses(): void
    {
        $modelClass = $this->commandHelper->retrieveNonEmptyString('Please enter Model class name');
        $modelClass = $this->makeFullyQualifiedModelClassName($modelClass);
        $modelClassExists = class_exists($modelClass);

        $displayModelClass = $this->commandHelper->retrieveString('Enter display model class name <return> for skipping display model');
        $displayModelClass = null !== $displayModelClass ? $this->makeFullyQualifiedModelClassName($displayModelClass) : null;
        $displayModelClassExists = null !== $displayModelClass && class_exists($displayModelClass);

        $modelMetadata = $modelClassExists ?
            $this->modelMetadataFactory->createMetadataFromModelClass($modelClass, $displayModelClassExists ? $displayModelClass : null) :
            $this->modelMetadataFactory->createMetadataForNonExistingClass($modelClass);

        if ($modelClassExists) {
            $this->commandHelper->addInfo("Model class exists- let's add new properties!");
        }
        $useNamedColumnKeys = $this->commandHelper->retrieveBoolean('Named column keys? If no, technical EXCEL columns will be expected');

        $this
            ->addPropertiesMetadata($modelMetadata, $useNamedColumnKeys, null !== $displayModelClass)
            ->rearrangeColumnKeyOrder($modelMetadata, $useNamedColumnKeys)
        ;

        $modelFileLocation = $this->resolveModelLocationFromClass($modelClass);
        $this->filesystem->dumpFile(
            $modelFileLocation,
            $this->twig->render('model.php.twig', [
                'importClasses' => $this->resolveClassesToImport($modelMetadata),
                'modelMetadata' => $modelMetadata,
                'emptyBoolAsFalse' => $this->emptyBoolAsFalse,
            ])
        );

        $displayModelFileLocation = null;
        if (null !== $displayModelClass) {
            $displayModelFileLocation = $this->resolveModelLocationFromClass($displayModelClass);
            $this->filesystem->dumpFile(
                $displayModelFileLocation,
                $this->twig->render('display_model.php.twig', [
                    'importClasses' => [AbstractDisplayModel::class],
                    'modelClass' => $displayModelClass,
                    'modelMetadata' => $modelMetadata,
                ])
            );
        }

        $this->commandHelper->addSuccess($this->createSuccessMessage($modelFileLocation, $displayModelFileLocation));
    }

    private function makeFullyQualifiedModelClassName(string $shortModelClassName): string
    {
        return self::MODEL_NAMESPACE . trim((preg_replace('|\\\{2,}|', '\\', $shortModelClassName)), '\\');
    }

    /**
     * @throws DuplicateExcelIdentifierException
     * @throws Exception
     * @throws InvalidAnnotationParamException
     * @throws UnexpectedAnnotationOptionException
     * @throws UnexpectedOptionExpectedDataTypeException
     * @throws UnexpectedOptionValueDataTypeException
     */
    private function addPropertiesMetadata(ModelMetadata $modelMetadata, bool $useNamedColumnKeys, bool $makeDisplayModelClass): self
    {
        while (null !== ($propertyName = $this->commandHelper->retrieveSTring('Enter property name (press <return> to stop adding fields)'))) {
            if ($modelMetadata->hasProperty($propertyName)) {
                $this->commandHelper->addWarning("Property $propertyName already exists");

                continue;
            }
            $columnKey = $this->commandHelper->retrieveNonEmptyString(
                'Enter EXCEL column key',
                !$useNamedColumnKeys ? Coordinate::stringFromColumnIndex($this->resolveColumnKeyIndexHint($modelMetadata)) : null
            );
            $columnKey = !$useNamedColumnKeys ? strtoupper($columnKey) : $columnKey;

            $isColumnKeyFieldId = $this->fieldIdResolver->isColumnKeyFieldIdentifier($columnKey);
            if (!$useNamedColumnKeys && !$isColumnKeyFieldId && !$this->isColumnKeyValidForNonNamedColumnNames($columnKey)) {
                $this->commandHelper->addWarning('Non-named column EXCEL column must be string of length less or equal to 3');

                continue;
            }
            $columnKeyExists = key_exists($columnKey, $modelMetadata->getModelPropertiesMetadata());
            $columnKeyInsertable = !$useNamedColumnKeys && !$isColumnKeyFieldId;
            if ($columnKeyExists && !$columnKeyInsertable) {
                $this->commandHelper->addWarning("Column with key $columnKey already exists");

                continue;
            } elseif (
                $columnKeyExists &&
                $columnKeyInsertable &&
                !$this->commandHelper->retrieveBoolean('Column already exists. Insert it anyway and move other properties?')
            ) {

                continue;
            }

            if ($columnKeyExists) {
                $this->createSpaceForInsertedPropertyMetadata($modelMetadata, $columnKey);
            }

            $cellName = $this->commandHelper->retrieveNonEmptyString('Enter EXCEL cell name');
            $targetExcelCellClass = $this->commandHelper->retrieveFromRange(
                'Choose target excel cell class',
                array_keys($this->excelCells),
                StringExcelCell::class
            );
            $isRequired = $this->commandHelper->retrieveBoolean('Is this field required?');

            $isInDisplayModel = $makeDisplayModelClass && $this->commandHelper->retrieveBoolean('Add field to display model?');

            $modelMetadata->addModelPropertyMetadata(
                $this->modelPropertyMetadataFactory->createForNonExistingProperty(
                    $columnKey,
                    $cellName,
                    $propertyName,
                    $targetExcelCellClass,
                    $isRequired,
                    $isInDisplayModel
                )
            );
        }

        return $this;
    }

    private function resolveColumnKeyIndexHint(ModelMetadata $modelMetadata): int
    {
        $modelPropertiesMetadata = $modelMetadata->getModelPropertiesMetadata();
        if (empty($modelPropertiesMetadata)) {

            return 1;
        }

        $columnKeys = array_filter(array_keys($modelMetadata->getModelPropertiesMetadata()), function (string $columnKey): bool {
            return strtoupper($columnKey) === $columnKey && !$this->fieldIdResolver->isColumnKeyFieldIdentifier($columnKey);
        });

        return max(array_map(static function (string $columnKey): int {
            try {
                return Coordinate::columnIndexFromString($columnKey);
            } catch (Exception $exception) {
                return 0;
            }
        }, $columnKeys) + [0]) + 1;
    }

    private function isColumnKeyValidForNonNamedColumnNames(string $columnKey): bool
    {
        return 1 === preg_match('|^[A-Z]{1,3}$|', $columnKey);
    }

    /**
     * @throws Exception
     * @throws DuplicateExcelIdentifierException
     */
    private function createSpaceForInsertedPropertyMetadata(ModelMetadata $modelMetadata, string $insertedColumnKey): void
    {
        $excelColumnMappedModelPropertiesMetadata = [];
        $columnKeyIndex = Coordinate::columnIndexFromString($insertedColumnKey);
        foreach ($this->getExcelColumnMappedPropertiesMetadata($modelMetadata) as $columnKey => $propertyMetadata) {
            $currentKeyIndex = Coordinate::columnIndexFromString($columnKey);
            if ($currentKeyIndex < $columnKeyIndex) {
                $excelColumnMappedModelPropertiesMetadata[$columnKey] = $propertyMetadata;

                continue;
            }
            $incrementedColumnIndex = Coordinate::stringFromColumnIndex($currentKeyIndex + 1);
            $excelColumnMappedModelPropertiesMetadata[$incrementedColumnIndex] = $propertyMetadata
                ->setColumnKey($incrementedColumnIndex)
                ->setExcelColumn($propertyMetadata->getExcelColumn()->setColumnKey($incrementedColumnIndex))
            ;
        }
        $modelMetadata->setModelPropertiesMetadata(
            $this->getFieldMappedPropertiesMetadata($modelMetadata) + $excelColumnMappedModelPropertiesMetadata
        );
    }

    /**
     * @throws Exception
     * @throws DuplicateExcelIdentifierException
     */
    private function rearrangeColumnKeyOrder(ModelMetadata $modelMetadata, bool $useNamedColumnNames): void
    {
        $modelPropertiesMetadata = $modelMetadata->getModelPropertiesMetadata();

        $fieldMappedPropertiesMetadata = $this->getFieldMappedPropertiesMetadata($modelMetadata);
        $nonFieldMappedPropertiesMetadata = array_diff_key($modelPropertiesMetadata, $fieldMappedPropertiesMetadata);

        if (!$useNamedColumnNames) {
            uksort( $nonFieldMappedPropertiesMetadata, static function (string $columnKey, string $comparingColumnKey): int {
                return Coordinate::columnIndexFromString($columnKey) <=> Coordinate::columnIndexFromString($comparingColumnKey);
            });
        }

        $modelMetadata->setModelPropertiesMetadata($fieldMappedPropertiesMetadata + $nonFieldMappedPropertiesMetadata);
    }

    /**
     * @return ModelPropertyMetadata[]
     */
    private function getExcelColumnMappedPropertiesMetadata(ModelMetadata $modelMetadata): array
    {
        return array_diff_key($modelMetadata->getModelPropertiesMetadata(), $this->getFieldMappedPropertiesMetadata($modelMetadata));
    }

    /**
     * @return ModelPropertyMetadata[]
     */
    private function getFieldMappedPropertiesMetadata(ModelMetadata $modelMetadata): array
    {
        return array_filter($modelMetadata->getModelPropertiesMetadata(), function (ModelPropertyMetadata $modelPropertyMetadata): bool {
            return $this->fieldIdResolver->isColumnKeyFieldIdentifier($modelPropertyMetadata->getColumnKey());
        });
    }

    /**
     * @return string[]
     *
     * @throws ReflectionException
     */
    private function resolveClassesToImport(ModelMetadata $modelMetadata): array
    {
        $excelCellClasses = array_values(array_unique(
            array_map(static function (ModelPropertyMetadata $modelPropertyMetadata): string {
                return $modelPropertyMetadata->getExcelColumn()->getTargetExcelCellClass();
            }, $modelMetadata->getModelPropertiesMetadata())
        ));
        $propertyTypes = array_unique(
            array_map(static function (ModelPropertyMetadata $modelPropertyMetadata): ?string {
                return $modelPropertyMetadata->getExpectedType();
            }, $modelMetadata->getModelPropertiesMetadata())
        );
        $propertyTypes = array_values(
            array_filter($propertyTypes, static function (string $typeName): bool {
                return class_exists($typeName);
            })
        );

        $importClasses = array_merge(
            $excelCellClasses,
            $propertyTypes,
            [ExcelColumn::class]
        );

        sort($importClasses);

        return $importClasses;
    }

    private function resolveModelLocationFromClass(string $class): string
    {
        return
            $this->projectDir .
            DIRECTORY_SEPARATOR .
            'src' .
            DIRECTORY_SEPARATOR .
            'Model' .
            DIRECTORY_SEPARATOR .
            'ExcelImporter' .
            DIRECTORY_SEPARATOR .
            str_replace('\\', DIRECTORY_SEPARATOR, str_replace(self::MODEL_NAMESPACE, '', $class)) .
            '.php';
    }

    private function createSuccessMessage(string $modelLocation, ?string $displayModelLocation): string
    {
        return 'Created following files:' .
            PHP_EOL .
            $modelLocation .
            (null !== $displayModelLocation ? PHP_EOL . $displayModelLocation : '');
    }
}