<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exporter;

use JetBrains\PhpStorm\ExpectedValues;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ReverseExcelCellManagerFactory;
use Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell\ReverseExcelCellManager;
use Kczer\ExcelImporterBundle\Exception\Annotation\AnnotationConfigurationException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\FileLoadException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Exception\Exporter\InvalidExcelImportException;
use Kczer\ExcelImporterBundle\Exception\Exporter\InvalidModelPropertyException;
use Kczer\ExcelImporterBundle\Exception\Exporter\NotGettablePropertyException;
use Kczer\ExcelImporterBundle\Exception\InvalidNamedColumnKeyException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelColumnsException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelFieldException;
use Kczer\ExcelImporterBundle\Exception\TemporaryFileManager\FileAlreadyExistsException;
use Kczer\ExcelImporterBundle\Exception\TemporaryFileManager\TemporaryFileCreationException;
use Kczer\ExcelImporterBundle\Exception\UnexpectedDisplayModelClassException;
use Kczer\ExcelImporterBundle\Importer\AbstractExcelImporter;
use Kczer\ExcelImporterBundle\Importer\Factory\ModelExcelImporterFactory;
use Kczer\ExcelImporterBundle\Model\ExcelRowsMetadata;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Kczer\ExcelImporterBundle\Util\TemporaryFileManager;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use function array_filter;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unshift;
use function current;
use function is_callable;
use function key;
use function range;
use function reset;
use function strtolower;
use function ucfirst;

class ModelExcelExporter
{
    public const XLSX_FILE_EXTENSION = 'xlsx';

    public const XLS_FILE_EXTENSION = 'xls';


    private ?ReverseExcelCellManager $reverseExcelCellManager = null;

    private ?array $columnKeyMappings = null;

    private ?string $modelClass = null;

    private ModelMetadata $modelMetadata;

    private ?ExcelRowsMetadata $excelRowsMetadata = null;

    /** @var object[] */
    private array $models = [];

    /** @var array<int, string[]> */
    private array $rawModelsData = [];

    public function __construct(
        private ModelMetadataFactory           $modelMetadataFactory,
        private ReverseExcelCellManagerFactory $reverseExcelCellManagerFactory,
        private TemporaryFileManager           $temporaryFileManager,
        private ModelExcelImporterFactory      $modelExcelImporterFactory
    ) {
    }

    private function setExcelRowsMetadata(ExcelRowsMetadata $excelRowsMetadata): self
    {
        $this->excelRowsMetadata = $excelRowsMetadata;

        return $this;
    }

    /**
     * @param object[] $models Models to export
     * @param string|null $newFileNameWithoutExtension Generated EXCEL file name without extension. random name if null provided
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
     * @throws FileLoadException
     */
    public function exportModelsToNewFile(
        array                                                  $models,
        string                                                 $newFileNameWithoutExtension = null,
        bool                                                   $outputHeaders = true,
        #[ExpectedValues(valuesFromClass: self::class)] string $fileExtension = self::XLSX_FILE_EXTENSION,
    ): string {
        if (empty($models)) {

            return $this->createExcelFileWithEmptyModels($newFileNameWithoutExtension, $fileExtension);
        }

        $this
            ->assignBasicDataBasedOnModels($models)
            ->prepareColumnKeyMappings()
            ->assignRawModelsData()
            ->transformRawModelsDataKeysIfRequired()
            ->transformModelMetadataPropertyKeysIfRequired()
        ;
        if ($outputHeaders) {
            $this->unshiftHeaderToRawModelsData();
        }

        return $this->exportRawModelsDataToNewFile($outputHeaders, $newFileNameWithoutExtension, $fileExtension);
    }

    /**
     * Merges models to data from existing EXCEL file and creates a new one
     *
     * @param (callable(object, object): bool)|string|null $comparer
     * Can be one of:
     *     <ul>
     *        <li>Model property name that will be compared between models to tell if they are the same </li>
     *        <li>Callable that takes two arguments : <br> function ($model1, $model2): bool. Returns true if models are considered the same </li>
     *        <li>NULL if no comparison needed </li>
     *     </ul>
     *
     * @throws AnnotationConfigurationException
     * @throws ExcelImportConfigurationException
     * @throws FileAlreadyExistsException
     * @throws FileLoadException
     * @throws InvalidExcelImportException
     * @throws InvalidModelPropertyException
     * @throws InvalidNamedColumnKeyException
     * @throws MissingExcelColumnsException
     * @throws MissingExcelFieldException
     * @throws NotGettablePropertyException
     * @throws ReflectionException
     * @throws TemporaryFileCreationException
     * @throws UnexpectedDisplayModelClassException
     * @throws UnexpectedExcelCellClassException
     * @throws Writer\Exception
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function exportAndMergeModelsToExistingFile(
        array                $models,
        string               $excelFilePath,
        string               $newFileNameWithoutExtension = null,
        callable|string|null $comparer = null,
        bool                 $namedColumnNames = true,
        int                  $firstRowMode = AbstractExcelImporter::FIRST_ROW_MODE_SKIP,
        #[ExpectedValues(valuesFromClass: self::class)] string $fileExtension = self::XLSX_FILE_EXTENSION,
    ): string {
        if (empty($models)) {

            return $this->createExcelFileWithEmptyModels($newFileNameWithoutExtension, $fileExtension, $excelFilePath);
        }
        $this->assignBasicDataBasedOnModels($models);

        $modelExcelImporter = $this->modelExcelImporterFactory->createModelExcelImporter($this->modelClass);
        $modelExcelImporter->parseExcelFile($excelFilePath, null, $namedColumnNames, $firstRowMode);
        if ($modelExcelImporter->hasErrors()) {

            throw new InvalidExcelImportException($this->modelClass, $modelExcelImporter->getMergedAllErrorMessages());
        }

        return $this
            ->mergeImportedToNewModels($modelExcelImporter->getModels(), $comparer)
            ->setExcelRowsMetadata($modelExcelImporter->getExcelRowsMetadata())
            ->prepareColumnKeyMappings()
            ->assignRawModelsData()
            ->transformRawModelsDataKeysIfRequired()
            ->transformModelMetadataPropertyKeysIfRequired()
            ->exportRawModelsDataToNewFile(true, $newFileNameWithoutExtension, $fileExtension, $excelFilePath)
        ;

    }

    /**
     * @param object[] $models
     *
     * @throws AnnotationConfigurationException
     * @throws ExcelImportConfigurationException
     * @throws ReflectionException
     */
    private function assignBasicDataBasedOnModels(array $models): self
    {
        $this->models = $models;
        $this
            ->assignModelClass()
            ->assignModelMetadata()
            ->assignReverseExcelCellManager();

        return $this;
    }

    private function assignModelClass(): self
    {
        $this->modelClass = current($this->models)::class;

        return $this;
    }

    /**
     * @throws ReflectionException
     * @throws ExcelImportConfigurationException
     * @throws AnnotationConfigurationException
     */
    private function assignModelMetadata(): self
    {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass($this->modelClass, null);

        return $this;
    }

    private function assignReverseExcelCellManager(): void
    {
        $this->reverseExcelCellManager = $this->reverseExcelCellManagerFactory->createFromModelMetadata($this->modelMetadata);
    }

    private function prepareColumnKeyMappings(): self
    {
        $importColumnKeyMappings = $this->excelRowsMetadata?->getColumnKeyMappings();
        if (null !== $importColumnKeyMappings) {
            $this->columnKeyMappings = $importColumnKeyMappings;

            return $this;
        }

        $rawModelsDataKeys = array_keys($this->modelMetadata->getModelPropertiesMetadata());
        if (
            empty(array_filter($rawModelsDataKeys, static function (string $columnKey): bool {
                return mb_strtoupper($columnKey) !== $columnKey;
            }))
        ) {

            return $this;
        }
        foreach ($rawModelsDataKeys as $index => $columnKey) {
            $this->columnKeyMappings[strtolower($columnKey)] = Coordinate::stringFromColumnIndex($index + 1);
        }

        return $this;
    }

    private function assignRawModelsData(): self
    {
        $this->rawModelsData = array_map([$this->reverseExcelCellManager, 'reverseModelToRawPropertyModels'], $this->models);
        if (null == $this->columnKeyMappings) {

            return $this;
        }

        $this->rawModelsData = array_map(static function (array $rawCellValues): array {
            return array_change_key_case($rawCellValues);
        }, $this->rawModelsData);

        return $this;
    }

    private function transformRawModelsDataKeysIfRequired(): self
    {
        if (null === $this->columnKeyMappings) {

            return $this;
        }

        $translatedRawModelsData = [];
        foreach ($this->rawModelsData as $rawModelData) {
            $translatedRawModelData = [];
            foreach ($rawModelData as $columnKeyName => $rawModelPropertyValue) {
                $translatedRawModelData[$this->columnKeyMappings[$columnKeyName]] = $rawModelPropertyValue;
            }
            $translatedRawModelsData[] = $translatedRawModelData;
        }
        $this->rawModelsData = $translatedRawModelsData;

        return $this;
    }

    private function transformModelMetadataPropertyKeysIfRequired(): self
    {
        if (null === $this->columnKeyMappings) {

            return $this;
        }
        $this->modelMetadata->transformColumnKeyNameKeysToExcelColumnKeys($this->columnKeyMappings);

        return $this;
    }

    private function unshiftHeaderToRawModelsData(): void
    {
        $headerData = [];
        foreach ($this->modelMetadata->getModelPropertiesMetadata() as $columnKey => $modelPropertyMetadata) {
            $headerData[$columnKey] = null !== $this->columnKeyMappings ? $modelPropertyMetadata->getColumnKey() : $modelPropertyMetadata->getCellName();
        }
        array_unshift($this->rawModelsData, $headerData);
    }

    /**
     * @param object[] $importedModels
     *
     * @throws InvalidModelPropertyException
     * @throws NotGettablePropertyException
     */
    private function mergeImportedToNewModels(array $importedModels, string|callable|null $comparer): self
    {
        if (null === $comparer) {
            $this->models = array_merge($importedModels, $this->models);

            return $this;
        }

        $isCompareCallable = is_callable($comparer);
        foreach ($importedModels as $importedModelKey => $importedModel) {
            foreach ($this->models as $modelKey => $model) {
                if ($isCompareCallable && ($comparer)($importedModel, $model)) {
                    $importedModels[$importedModelKey] = $model;
                    unset($this->models[$modelKey]);

                    break;
                } elseif (!$isCompareCallable) {
                    $propertyGetterName = $this->modelMetadata->getPropertyGetterName($comparer);
                    if ($importedModel->{$propertyGetterName}() == $model->{$propertyGetterName}()) { // Weak comparison to work with objects properly
                        $importedModels[$importedModelKey] = $model;
                        unset($this->models[$modelKey]);

                        break;
                    }
                }
            }
        }
        $this->models = array_merge($importedModels, $this->models);

        return $this;
    }

    /**
     * @throws FileAlreadyExistsException
     * @throws TemporaryFileCreationException
     * @throws FileLoadException
     * @throws Writer\Exception
     */
    private function createExcelFileWithEmptyModels(
        ?string $fileNameWithoutExtension,
        string  $fileExtension,
        string  $excelToCopyFileFullPath = null
    ): string {
        if (null === $excelToCopyFileFullPath) {
            $newFilePath = $this->temporaryFileManager->createTmpFileWithNameAndExtension($fileNameWithoutExtension, $fileExtension);
            IOFactory::createWriter(new Spreadsheet(), ucfirst($fileExtension))->save($newFilePath);

            return $newFilePath;
        }

        return $this->temporaryFileManager->createTmpFileWithExtensionFromExistingFile(
            $fileNameWithoutExtension,
            $fileExtension,
            $excelToCopyFileFullPath
        );
    }

    /**
     * @throws Writer\Exception
     * @throws FileAlreadyExistsException
     * @throws TemporaryFileCreationException
     *
     * @noinspection PhpUnnecessaryCurlyVarSyntaxInspection
     */
    private function exportRawModelsDataToNewFile(
        bool    $outputHeaders,
        ?string $fileNameWithoutExtension,
        string  $fileExtension,
        string  $existingExcelFIleFullPath = null
    ): string {
        $spreadsheet = null !== $existingExcelFIleFullPath ? IOFactory::load($existingExcelFIleFullPath) : new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $isExportMerging = null !== $this->excelRowsMetadata;
        if ($isExportMerging) {
            $this->clearExportWorkspaceOnSheet($sheet);
        }

        $workspaceStart = $isExportMerging ? $this->excelRowsMetadata->getFirstDataRowIndex() : 0;
        reset($this->rawModelsData);
        foreach ($this->rawModelsData as $index => $rawModelData) {
            $excelIndex = $index + $workspaceStart + 1;
            if (!$outputHeaders && key($this->rawModelsData) === $index) {

                continue;
            }
            foreach ($rawModelData as $columnKey => $rawModelPropertyValue) {
                $sheet->setCellValue("{$columnKey}{$excelIndex}", $rawModelPropertyValue);
            }
        }

        $newFilePath = $this->temporaryFileManager->createTmpFileWithNameAndExtension($fileNameWithoutExtension, $fileExtension);
        IOFactory::createWriter($spreadsheet, ucfirst($fileExtension))->save($newFilePath);

        return $newFilePath;
    }

    /**
     * @param Worksheet $sheet
     *
     * @noinspection PhpUnnecessaryCurlyVarSyntaxInspection
     */
    private function clearExportWorkspaceOnSheet(Worksheet $sheet): void
    {
        $workspaceColumnKeys = array_keys($this->modelMetadata->getModelPropertiesMetadata());
        foreach (range($this->excelRowsMetadata->getFirstDataRowIndex(), $this->excelRowsMetadata->getLastDataRowIndex()) as $index) {
            $excelIndex = $index + 1;
            foreach ($workspaceColumnKeys as $columnKey) {
                $sheet->setCellValue("{$columnKey}{$excelIndex}", null);
            }
        }
    }
}