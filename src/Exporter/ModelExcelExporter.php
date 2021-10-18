<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exporter;

use Kczer\ExcelImporterBundle\ExcelElement\Factory\ReverseExcelCellManagerFactory;
use Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell\ReverseExcelCellManager;
use Kczer\ExcelImporterBundle\Exception\Annotation\AnnotationConfigurationException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer;
use ReflectionException;
use function array_combine;
use function array_filter;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function array_unshift;
use function current;
use function get_class;
use function key;
use function reset;
use function sys_get_temp_dir;
use function tempnam;

class ModelExcelExporter
{
    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var ReverseExcelCellManagerFactory */
    private $reverseExcelCellManagerFactory;

    /** @var ReverseExcelCellManager|null */
    private $reverseExcelCellManager;

    /** @var array<string, string>|null */
    private $columnKeyMappings;

    /** @var object[] */
    private $models = [];

    /** @var array<int, string[]> */
    private $rawModelsData = [];

    public function __construct(
        ModelMetadataFactory $modelMetadataFactory,
        ReverseExcelCellManagerFactory $reverseExcelCellManagerFactory
    )
    {
        $this->modelMetadataFactory = $modelMetadataFactory;
        $this->reverseExcelCellManagerFactory = $reverseExcelCellManagerFactory;
    }

    /**
     * @param object[] $models
     * @param bool $outputHeaders
     *
     * @return string
     *
     * @throws AnnotationConfigurationException
     * @throws ExcelImportConfigurationException
     * @throws ReflectionException
     * @throws Writer\Exception
     */
    public function exportModels(array $models, bool $outputHeaders = true): string
    {
        $this->models = $models;
        $this
            ->assignModelMetadata()
            ->assignReverseExcelCellManager()
            ->assignRawModelsData()
            ->prepareColumnKeyMappings()
            ->transformRawModelsDataKeysIfRequired()
            ->transformModelMetadataPropertyKeysIfRequired();
        if ($outputHeaders) {
            $this->unshiftHeaderToRawModelsData();
        }

        return $this->exportRawModelsDataToNewFile($outputHeaders);
    }

    /**
     * @throws ReflectionException
     * @throws ExcelImportConfigurationException
     * @throws AnnotationConfigurationException
     */
    private function assignModelMetadata(): self
    {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass(get_class(current($this->models)), null);

        return $this;
    }

    private function assignReverseExcelCellManager(): self
    {
        $this->reverseExcelCellManager = $this->reverseExcelCellManagerFactory->createFromModelMetadata($this->modelMetadata);

        return $this;
    }

    private function assignRawModelsData(): self
    {
        $this->rawModelsData = array_map([$this->reverseExcelCellManager, 'reverseModelToRawPropertyModels'], $this->models);

        return $this;
    }

    private function prepareColumnKeyMappings(): self
    {
        $rawModelsDataKeys = array_keys($this->modelMetadata->getModelPropertiesMetadata());
        if (empty(array_filter($rawModelsDataKeys, static function (string $columnKey): bool {
            return mb_strtoupper($columnKey) !== $columnKey;
        }))) {

            return $this;
        }
        foreach ($rawModelsDataKeys as $index => $columnKey) {
            $this->columnKeyMappings[$columnKey] = Coordinate::stringFromColumnIndex($index + 1);
        }

        return $this;
    }

    private function transformRawModelsDataKeysIfRequired(): self
    {
        if (null === $this->columnKeyMappings) {

            return $this;
        }
        $translatedRawModelsData = [];
        foreach ($this->rawModelsData as $rawModelData) {
            $translatedRawModelsData[] = array_combine(
                array_intersect_key($this->columnKeyMappings, $rawModelData),
                array_intersect_key($rawModelData, $this->columnKeyMappings)
            );
        }
        $this->rawModelsData = $translatedRawModelsData;

        return $this;
    }

    private function transformModelMetadataPropertyKeysIfRequired(): void
    {
        if (null === $this->columnKeyMappings) {

            return;
        }

        $this->modelMetadata->transformColumnKeyNameKeysToExcelColumnKeys($this->columnKeyMappings);
    }

    private function unshiftHeaderToRawModelsData(): void
    {
        $headerData = [];
        foreach ($this->modelMetadata->getModelPropertiesMetadata() as $columnKey => $modelPropertyMetadata) {
            $excelColumn = $modelPropertyMetadata->getExcelColumn();
            $headerData[$columnKey] = null !== $this->columnKeyMappings ? $excelColumn->getColumnKey() : $excelColumn->getCellName();
        }
        array_unshift($this->rawModelsData, $headerData);
    }

    /**
     * @throws Writer\Exception
     */
    private function exportRawModelsDataToNewFile(bool $outputHeaders): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        reset($this->rawModelsData);
        foreach ($this->rawModelsData as $index => $rawModelData) {
            if (!$outputHeaders && key($this->rawModelsData) === $index) {

                continue;
            }
            foreach ($rawModelData as $columnKey => $rawModelPropertyValue) {
                $excelIndex = $index + 1;
                $sheet->setCellValue("{$columnKey}{$excelIndex}", $rawModelPropertyValue);
            }
        }

        $newFilePath = tempnam(sys_get_temp_dir(), 'tmp');
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($newFilePath);

        return $newFilePath;
    }
}