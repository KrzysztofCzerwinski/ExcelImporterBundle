<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer;

use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelCellFactory;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelRowFactory;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidDisplayModelSetterParameterTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\ModelPropertyNotSettableException;
use Kczer\ExcelImporterBundle\Exception\Annotation\NotExistingModelClassException;
use Kczer\ExcelImporterBundle\Exception\Annotation\SetterNotCompatibleWithExcelCellValueException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedColumnExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\DuplicateExcelIdentifierException;
use Kczer\ExcelImporterBundle\Exception\EmptyModelClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Exception\Exporter\InvalidModelPropertyException;
use Kczer\ExcelImporterBundle\Exception\InvalidNamedColumnKeyException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelColumnsException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelFieldException;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractImportValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\Factory\ImportValidatorFactory;
use Kczer\ExcelImporterBundle\Model\Factory\ModelFactory;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Kczer\ExcelImporterBundle\Model\AbstractDisplayModel;
use Kczer\ExcelImporterBundle\Util\FieldIdResolver;
use ReflectionException;
use Symfony\Contracts\Translation\TranslatorInterface;
use function current;

/**
 * @template TM
 * @template TD
 */
class ModelExcelImporter extends AbstractExcelImporter
{
    /** @var class-string<TM>|null */
    private ?string $importModelClass = null;

    /** @var class-string<AbstractDisplayModel>|class-string<TD>|null */
    private ?string $displayModelClass = null;

    private ModelMetadata $modelMetadata;

    /** @var object[] */
    private array $models = [];

    /** @var AbstractDisplayModel[] */
    private array $displayModels = [];

    /** @var AbstractImportValidator[] */
    private array $validators = [];

    public function __construct (
        FieldIdResolver                $fieldIdResolver,
        ExcelCellFactory               $excelCellFactory,
        ExcelRowFactory                $excelRowFactory,
        private ModelMetadataFactory   $modelMetadataFactory,
        private ModelFactory           $modelFactory,
        private ImportValidatorFactory $importValidatorFactory,
        private TranslatorInterface    $translator,
    ) {
        parent::__construct($fieldIdResolver, $excelCellFactory, $excelRowFactory);
    }

    /**
     * @return TM[] Array of models associated with ModelClass
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * @return AbstractDisplayModel[]|TD[]
     */
    public function getDisplayModels(): array
    {
        return $this->displayModels;
    }

    /**
     * @throws EmptyModelClassException
     */
    public function getImportModelClass(): string
    {
        if (null === $this->importModelClass) {

            throw new EmptyModelClassException();
        }
        return $this->importModelClass;
    }

    public function setImportModelClass(string $importModelClass): static
    {
        $this->importModelClass = $importModelClass;

        return $this;
    }

    public function setDisplayModelClass(?string $displayModelClass): ModelExcelImporter
    {
        $this->displayModelClass = $displayModelClass;
        return $this;
    }

    /**
     * @return TM|null First model associated with the import or nul if no models are present
     */
    public function getFirstModel(): ?object
    {
        $models = $this->getModels();

        return !empty($models) ? current($models) : null;
    }

    /**
     * @return TD|null First model associated with the import or null if no models are present
     */
    public function getFirstDisplayModel(): ?AbstractDisplayModel
    {
        $displayModels = $this->getDisplayModels();

        return !empty($displayModels) ? current($displayModels) : null;
    }


    /**
     * @throws DuplicateExcelIdentifierException
     * @throws EmptyModelClassException
     * @throws InvalidDisplayModelSetterParameterTypeException
     * @throws MissingExcelColumnsException
     * @throws ModelPropertyNotSettableException
     * @throws NotExistingModelClassException
     * @throws ReflectionException
     * @throws UnexpectedColumnExcelCellClassException
     * @throws SetterNotCompatibleWithExcelCellValueException
     * @throws UnexpectedExcelCellClassException
     * @throws InvalidNamedColumnKeyException
     * @throws MissingExcelFieldException
     * @throws InvalidModelPropertyException
     */
    protected function parseRawExcelRows(
        int     $firstRowMode,
        ?string $indexBy,
        bool    $namedColumnKeys,
        array   $options,
    ): void {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass($this->getImportModelClass(), $this->displayModelClass);
        if (null !== $indexBy) {
            $this->indexByColumnKey = $this->modelMetadata->getPropertyMetadataByName($indexBy)->getColumnKey();
        }
        parent::parseRawExcelRows($firstRowMode, $indexBy, $namedColumnKeys, $options);
        if (null !== $this->columnKeyMappings) {
            $this->modelMetadata->transformColumnKeyNameKeysToExcelColumnKeys($this->columnKeyMappings);
        }
        $this->validators = $this->importValidatorFactory->createFromImportModelClass($this->getImportModelClass());
        $this->models = $this->modelFactory->createImportedAssociatedModelsFromExcelRowsAndModelMetadata(
            $this->getImportModelClass(),
            $this->modelIndexedExcelRows,
            $this->modelMetadata
        );
        if (null !== $this->displayModelClass) {
            $this->displayModels = $this->modelFactory->createDisplayModelsFromExcelRowsAndModelMetadata(
                $this->displayModelClass,
                $this->modelIndexedExcelRows,
                $this->modelMetadata
            );
        }
        $this->validateImport();
    }

    /**
     * @throws ExcelImportConfigurationException
     * @throws UnexpectedClassException
     */
    protected function configureExcelCells(): static
    {
        foreach ($this->modelMetadata->getModelPropertiesMetadata() as $columnKey => $propertyMetadata) {
            $this->addExcelCell(
                $propertyMetadata->getTargetExcelCellClass(),
                $propertyMetadata->getCellName(),
                $columnKey,
                $propertyMetadata->isRequired(),
                !$this->fieldIdResolver->isColumnKeyFieldIdentifier($propertyMetadata->getColumnKey()),
                $propertyMetadata->getValidators()
            );
        }

        return $this;
    }

    private function validateImport(): void
    {
        foreach ($this->validators as $validator) {
            $isValid = $validator->isImportValid($this->getExcelRows(), $this->modelMetadata);
            if ($isValid) {

                continue;
            }
            $this->importRelateErrorMessages[$validator::class] =
                $this->translator->trans(...$validator->getMessageWithParams());
        }
    }
}