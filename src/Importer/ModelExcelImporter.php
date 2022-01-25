<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer;

use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelCellFactory;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelRowFactory;
use Kczer\ExcelImporterBundle\Exception\Annotation\AnnotationConfigurationException;
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
use Kczer\ExcelImporterBundle\Exception\InvalidNamedColumnKeyException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelColumnsException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelFieldException;
use Kczer\ExcelImporterBundle\Model\Factory\ModelFactory;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Kczer\ExcelImporterBundle\Model\AbstractDisplayModel;
use ReflectionException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModelExcelImporter extends AbstractExcelImporter
{
    /** @var class-string */
    private $importModelClass;

    /** @var class-string<AbstractDisplayModel>|null */
    private $displayModelClass = null;

    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var object[] */
    private $models = [];

    /** @var AbstractDisplayModel[] */
    private $displayModels = [];


    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var ModelFactory */
    private $modelFactory;

    public function __construct
    (
        TranslatorInterface $translator,
        ExcelCellFactory $excelCellFactory,
        ExcelRowFactory $excelRowFactory,
        ModelMetadataFactory $modelMetadataFactory,
        ModelFactory $modelFactory
    )
    {
        parent::__construct($translator, $excelCellFactory, $excelRowFactory);
        $this->modelMetadataFactory = $modelMetadataFactory;
        $this->modelFactory = $modelFactory;
    }


    /**
     * @return object[] Array of models associated with ModelClass
     */
    public function getModels(): array
    {
        return $this->models;
    }

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
     */
    protected function parseRawExcelRows(int $firstRowMode, bool $namedColumnKeys): void
    {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass($this->getImportModelClass(), $this->displayModelClass);
        parent::parseRawExcelRows($firstRowMode, $namedColumnKeys);
        if (null !== $this->columnKeyMappings) {
            $this->modelMetadata->transformColumnKeyNameKeysToExcelColumnKeys($this->columnKeyMappings);
        }
        $this->models = $this->modelFactory->createImportedAssociatedModelsFromExcelRowsAndModelMetadata(
            $this->getImportModelClass(),
            $this->getExcelRows(),
            $this->modelMetadata
        );
        if (null !== $this->displayModelClass) {
            $this->displayModels = $this->modelFactory->createDisplayModelsFromExcelRowsAndModelMetadata(
                $this->displayModelClass,
                $this->getExcelRows(),
                $this->modelMetadata
            );
        }
    }

    /**
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
                !$propertyExcelColumn->isField(),
                $propertyMetadata->getValidators()
            );
        }

        return $this;
    }
}