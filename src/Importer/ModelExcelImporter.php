<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer;

use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelCellFactory;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelRowFactory;
use Kczer\ExcelImporterBundle\Exception\Annotation\AnnotationConfigurationException;
use Kczer\ExcelImporterBundle\Exception\EmptyExcelColumnException;
use Kczer\ExcelImporterBundle\Exception\EmptyModelClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Model\Factory\ModelFactory;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;


class ModelExcelImporter extends AbstractExcelImporter
{
    /** @var string */
    private $importModelClass;

    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var ModelFactory */
    private $modelFactory;

    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var array */
    private $models = [];


    public function __construct
    (
        ExcelCellFactory $excelCellFactory,
        ExcelRowFactory $excelRowFactory,
        ModelMetadataFactory $modelMetadataFactory,
        ModelFactory $modelFactory
    )
    {
        parent::__construct($excelCellFactory, $excelRowFactory);
        $this->modelMetadataFactory = $modelMetadataFactory;
        $this->modelFactory = $modelFactory;
    }


    /**
     * @return array Array of models associated with ModelClass
     *
     * @warning Array will be empty if import has any errors
     */
    public function getModels(): array
    {
        return $this->models;
    }

    /**
     * @throws EmptyModelClassException
     */
    public function getImportModelClass(): string
    {
        if (null === $this->importModelClass) {

            throw new EmptyModelClassException(static::class);
        }
        return $this->importModelClass;
    }

    public function setImportModelClass(string $importModelClass): self
    {
        $this->importModelClass = $importModelClass;

        return $this;
    }

    /**
     * @throws UnexpectedExcelCellClassException
     * @throws EmptyExcelColumnException
     * @throws AnnotationConfigurationException
     * @throws ExcelImportConfigurationException
     */
    protected function parseRawExcelRows(array $rawExcelRows, int $firstRowMode): void
    {
        $this->assignModelMetadata();
        parent::parseRawExcelRows($rawExcelRows, $firstRowMode);
        $this->models = $this->modelFactory->createModelsFromExcelRowsAndModelMetadata($this->getImportModelClass(), $this->getExcelRows(), $this->modelMetadata);
    }

    /**
     * @throws UnexpectedClassException
     */
    protected function configureExcelCells(): void
    {
        foreach ($this->modelMetadata->getModelPropertiesMetadata() as $columnKey => $propertyMetadata) {
            $propertyExcelColumn = $propertyMetadata->getExcelColumn();

            $this->addExcelCell($propertyExcelColumn->getTargetExcelCellClass(), $propertyExcelColumn->getCellName(), $columnKey, $propertyExcelColumn->isRequired(), $propertyMetadata->getValidators());
        }
    }

    /**
     * @throws ExcelImportConfigurationException
     * @throws AnnotationConfigurationException
     */
    private function assignModelMetadata(): void
    {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass($this->getImportModelClass());
    }
}