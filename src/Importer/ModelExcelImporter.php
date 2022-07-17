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
    /** @var class-string<TM> */
    private $importModelClass;

    /** @var class-string<AbstractDisplayModel>|class-string<TD>|null */
    private $displayModelClass = null;

    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var object[] */
    private $models = [];

    /** @var AbstractDisplayModel[] */
    private $displayModels = [];

    /** @var AbstractImportValidator[] */
    private $validators = [];


    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var ModelFactory */
    private $modelFactory;

    /** @var ImportValidatorFactory */
    private $importValidatorFactory;

    /** @var TranslatorInterface */
    private $translator;

    public function __construct (
        ExcelCellFactory       $excelCellFactory,
        ExcelRowFactory        $excelRowFactory,
        FieldIdResolver        $fieldIdResolver,
        ModelMetadataFactory   $modelMetadataFactory,
        ModelFactory           $modelFactory,
        ImportValidatorFactory $importValidatorFactory,
        TranslatorInterface    $translator
    ) {
        parent::__construct($excelCellFactory, $excelRowFactory, $fieldIdResolver);
        $this->modelMetadataFactory = $modelMetadataFactory;
        $this->modelFactory = $modelFactory;
        $this->importValidatorFactory = $importValidatorFactory;
        $this->translator = $translator;
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

    public function setImportModelClass(string $importModelClass): self
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
    protected function parseRawExcelRows(int $firstRowMode, ?string $indexBy, bool $namedColumnKeys): void
    {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass($this->getImportModelClass(), $this->displayModelClass);
        if (null !== $indexBy) {
            $this->indexByColumnKey = $this->modelMetadata->getPropertyMetadataByName($indexBy)->getColumnKey();
        }
        parent::parseRawExcelRows($firstRowMode, $indexBy, $namedColumnKeys);
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
     * @return $this
     *
     * @throws ExcelImportConfigurationException
     * @throws UnexpectedClassException
     */
    protected function configureExcelCells(): AbstractExcelImporter
    {
        foreach ($this->modelMetadata->getModelPropertiesMetadata() as $columnKey => $propertyMetadata) {
            $propertyExcelColumn = $propertyMetadata->getExcelColumn();

            $this->addExcelCell(
                $propertyExcelColumn->getTargetExcelCellClass(),
                $propertyExcelColumn->getCellName(),
                $columnKey,
                $propertyExcelColumn->isRequired(),
                !$this->fieldIdResolver->isColumnKeyFieldIdentifier($propertyExcelColumn->getColumnKey()),
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