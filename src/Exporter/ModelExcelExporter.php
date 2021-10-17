<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exporter;

use Kczer\ExcelImporterBundle\ExcelElement\Factory\ReverseExcelCellManagerFactory;
use Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell\ReverseExcelCellManager;
use Kczer\ExcelImporterBundle\Exception\Annotation\AnnotationConfigurationException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use ReflectionException;
use function array_map;
use function current;
use function get_class;

class ModelExcelExporter
{
    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var object[] */
    private $models = [];

    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var ReverseExcelCellManagerFactory */
    private $reverseExcelCellManagerFactory;

    /** @var ReverseExcelCellManager|null */
    private $reverseExcelCellManager;

    public function __construct(
        ModelMetadataFactory $modelMetadataFactory,
        ReverseExcelCellManagerFactory $reverseExcelCellManagerFactory
    )
    {
        $this->modelMetadataFactory = $modelMetadataFactory;
        $this->$reverseExcelCellManagerFactory = $reverseExcelCellManagerFactory;
    }

    /**
     * @throws ReflectionException
     * @throws AnnotationConfigurationException
     * @throws ExcelImportConfigurationException
     */
    public function exportModel(array $models): void
    {
        $this->models = $models;
        $this
            ->assignModelMetadata()
            ->assignReverseExcelCellManager();

        $modelsRawData = array_map([$this->reverseExcelCellManager, 'reverseModelToArray'], $models);
    }

    /**
     * @throws ReflectionException
     * @throws ExcelImportConfigurationException
     * @throws AnnotationConfigurationException
     */
    protected function assignModelMetadata(): self
    {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass(get_class(current($this->models)), null);

        return $this;
    }

    protected function assignReverseExcelCellManager(): self
    {
        $this->reverseExcelCellManager = $this->reverseExcelCellManagerFactory->createFromModelMetadata($this->modelMetadata);

        return $this;
    }
}