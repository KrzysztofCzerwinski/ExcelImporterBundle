<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exporter;

use Kczer\ExcelImporterBundle\ExcelElement\Factory\ReverseExcelCellManagerFactory;
use Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell\ReverseExcelCellManager;
use Kczer\ExcelImporterBundle\Exception\Annotation\AnnotationConfigurationException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Exception\TemporaryFileManager\FileAlreadyExistsException;
use Kczer\ExcelImporterBundle\Exception\TemporaryFileManager\TemporaryFileCreationException;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Kczer\ExcelImporterBundle\Util\TemporaryFileManager;
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
use function ucfirst;

class ModelExcelExporter
{
    public const EXCEL_FILE_EXTENSION = 'xlsx';

    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var ReverseExcelCellManagerFactory */
    private $reverseExcelCellManagerFactory;

    /** @var ReverseExcelCellManager|null */
    private $reverseExcelCellManager;

    /** @var TemporaryFileManager */
    private $temporaryFileManager;

    /** @var array<string, string>|null */
    private $columnKeyMappings;

    /** @var object[] */
    private $models = [];

    /** @var array<int, string[]> */
    private $rawModelsData = [];

    public function __construct(
        ModelMetadataFactory $modelMetadataFactory,
        ReverseExcelCellManagerFactory $reverseExcelCellManagerFactory,
        TemporaryFileManager $temporaryFileManager
    )
    {
        $this->modelMetadataFactory = $modelMetadataFactory;
        $this->reverseExcelCellManagerFactory = $reverseExcelCellManagerFactory;
        $this->temporaryFileManager = $temporaryFileManager;
    }

    /**
     * @param object[] $models Models to export
     * @param string|null $fileNameWithoutExtension Generate EXCEL file name without extension. random name if null provided
     * @param bool $outputHeaders Whether to add header columns
     *
     * @return string Newly created EXCEL file full name
     *
     * @throws AnnotationConfigurationException
     * @throws ExcelImportConfigurationException
     * @throws FileAlreadyExistsException
     * @throws ReflectionException
     * @throws TemporaryFileCreationException
     * @throws Writer\Exception
     */
    public function exportModels(array $models, string $fileNameWithoutExtension = null, bool $outputHeaders = true): string
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

        return $this->exportRawModelsDataToNewFile($outputHeaders, $fileNameWithoutExtension);
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
        if (
            empty(array_filter($rawModelsDataKeys, static function (string $columnKey): bool {
                return mb_strtoupper($columnKey) !== $columnKey;
            }))
        ) {

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
     * @throws FileAlreadyExistsException
     * @throws TemporaryFileCreationException
     */
    private function exportRawModelsDataToNewFile(bool $outputHeaders, ?string $fileNameWithoutExtension): string
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

        $newFilePath = $this->temporaryFileManager->createTmpFileWithNameAndExtension($fileNameWithoutExtension, self::EXCEL_FILE_EXTENSION);
        IOFactory::createWriter($spreadsheet, ucfirst(self::EXCEL_FILE_EXTENSION))->save($newFilePath);

        return $newFilePath;
    }
}