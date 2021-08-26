<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle;

use Kczer\ExcelImporterBundle\Exception\Annotation\ModelExcelColumnConfigurationException;
use Kczer\ExcelImporterBundle\Exception\EmptyExcelColumnException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Model\Factory\ModelFactory;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;


abstract class AbstractModelExcelImporter extends AbstractExcelImporter
{
    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var ModelFactory */
    private $modelFactory;

    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var array */
    private $models = [];


    public function __construct(ModelMetadataFactory $modelMetadataFactory, ModelFactory $modelFactory)
    {
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
     * Do something with parsed data (models available via getModels())
     */
    public abstract function processParsedData(): void;

    /**
     * @return string Fully qualified class name of model attached to this Importer instance
     */
    protected abstract function getImportModelClass(): string;

    /**
     * @throws UnexpectedExcelCellClassException
     * @throws EmptyExcelColumnException
     * @throws ModelExcelColumnConfigurationException
     * @throws ExcelImportConfigurationException
     */
    protected function parseRawExcelRows(array $rawExcelRows, bool $skipFirstRow = true): void
    {
        $this->assignModelMetadata();
        parent::parseRawExcelRows($rawExcelRows, $skipFirstRow);
        if (!$this->hasErrors()) {
            $this->models = $this->modelFactory->createModelsFromExcelRowsAndModelMetadata($this->getImportModelClass(), $this->getExcelRows(), $this->modelMetadata);
        }
    }

    /**
     * @throws UnexpectedExcelCellClassException
     */
    protected function configureExcelCells(): void
    {
        foreach ($this->modelMetadata->getModelPropertiesMetadata() as $columnKey => $propertyMetadata) {
            $propertyExcelColumn = $propertyMetadata->getExcelColumn();

            $this->addExcelCell($propertyExcelColumn->getTargetExcelCellClass(), $propertyExcelColumn->getCellName(), $columnKey, $propertyExcelColumn->isRequired());
        }
    }

    /**
     * @throws ExcelImportConfigurationException
     * @throws ModelExcelColumnConfigurationException
     */
    private function assignModelMetadata(): void
    {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass($this->getImportModelClass());
    }
}