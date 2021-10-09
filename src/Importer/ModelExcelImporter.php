<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer;

use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelCellFactory;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelRowFactory;
use Kczer\ExcelImporterBundle\Exception\Annotation\AnnotationConfigurationException;
use Kczer\ExcelImporterBundle\Exception\EmptyExcelColumnException;
use Kczer\ExcelImporterBundle\Exception\EmptyModelClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Model\Factory\ModelFactory;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Kczer\ExcelImporterBundle\Model\AbstractDisplayModel;
use ReflectionException;
use Symfony\Contracts\Translation\TranslatorInterface;

class ModelExcelImporter extends AbstractExcelImporter
{
    /** @var string */
    private $importModelClass;

    /** @var string|null */
    private $displayModelClass = null;

    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var ModelFactory */
    private $modelFactory;

    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var array */
    private $models = [];

    /** @var AbstractDisplayModel[] */
    private $displayModels = [];


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
     * @return array Array of models associated with ModelClass
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
     * @throws EmptyExcelColumnException
     * @throws AnnotationConfigurationException
     * @throws ExcelImportConfigurationException
     * @throws ReflectionException
     */
    protected function parseRawExcelRows(int $firstRowMode): void
    {
        $this->assignModelMetadata();
        parent::parseRawExcelRows($firstRowMode);
        if (null !== $this->columnKeyMappings) {
            $this->modelMetadata->transformColumnKeyNameKeysToExcelColumnKeys($this->columnKeyMappings);
        }
        $this->models = $this->modelFactory->createImportedAssociatedModelsFromExcelRowsAndModelMetadata($this->getImportModelClass(), $this->getExcelRows(), $this->modelMetadata);
        if (null !== $this->displayModelClass) {
            $this->displayModels = $this->modelFactory->createDisplayModelsFromExcelRowsAndModelMetadata($this->displayModelClass, $this->getExcelRows(), $this->modelMetadata);
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

            $this->addExcelCell($propertyExcelColumn->getTargetExcelCellClass(), $propertyExcelColumn->getCellName(), $columnKey, $propertyExcelColumn->isRequired(), $propertyMetadata->getValidators());
        }

        return $this;
    }

    /**
     * @throws ExcelImportConfigurationException
     * @throws AnnotationConfigurationException
     * @throws ReflectionException
     */
    private function assignModelMetadata(): void
    {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass($this->getImportModelClass(), $this->displayModelClass);
    }
}