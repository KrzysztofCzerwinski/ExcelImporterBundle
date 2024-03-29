<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer;

use JetBrains\PhpStorm\ExpectedValues;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration\ExcelCellConfiguration;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelCellFactory;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelRowFactory;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\FileLoadException;
use Kczer\ExcelImporterBundle\Exception\InvalidNamedColumnKeyException;
use Kczer\ExcelImporterBundle\Exception\JsonExcelRowsLoadException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelColumnsException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelFieldException;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractImportValidator;
use Kczer\ExcelImporterBundle\Model\ExcelRowsMetadata;
use Kczer\ExcelImporterBundle\Model\Factory\ExcelRowsMetadataFactory;
use Kczer\ExcelImporterBundle\Util\FieldIdResolver;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;
use function array_change_key_case;
use function array_filter;
use function array_flip;
use function array_keys;
use function array_map;
use function array_search;
use function array_slice;
use function array_udiff;
use function array_uintersect;
use function count;
use function current;
use function implode;
use function json_decode;
use function json_encode;
use function key;
use function ksort;
use function reset;
use function strtolower;
use function trim;

abstract class AbstractExcelImporter
{
    public const FIRST_ROW_MODE_SKIP = 1;

    public const FIRST_ROW_MODE_DONT_SKIP = 2;

    public const FIRST_ROW_MODE_SKIP_IF_INVALID = 4;


    /** @var array<string, string>|null
     *       Array with keys as human-readable column keys and values as EXCEL column keys with A-Z notation.
     *       Null if no mapping is required
     */
    protected ?array $columnKeyMappings = null;

    /** @var string|null Excel identifier of indexBy property */
    protected ?string $indexByColumnKey = null;

    /** @var array<int, array<string, string>>|null */
    private ?array $rawExcelRows = [];

    /** @var array<int, array<string, string>> */
    private array $fieldMappedPreHeaderRows = [];

    /** @var array<string, ExcelCellConfiguration> */
    private array $columnMappedExcelCellConfigurations = [];

    /**
     * @var array<int, array<string, ExcelCellConfiguration>>
     *      Keys are row number, and then column key
     */
    private array $fieldMappedExcelCellConfigurations = [];

    /** @var array<int, ExcelRow>*/
    private array $excelRows = [];

    private ?int $headerRowIndex = null;

    private ExcelRowsMetadata $excelRowsMetadata;

    /** @var (callable(ExcelRow[]): void)|null */
    private $rowRequirementsValidator = null;

    /** @var array<class-string<AbstractImportValidator>, string> */
    protected array $importRelateErrorMessages = [];

    /** @var array<string|int, ExcelRow> Keys are either EXCEL row numbers or model property values */
    protected array $modelIndexedExcelRows = [];

    public function __construct(
        protected FieldIdResolver $fieldIdResolver,
        private ExcelCellFactory  $excelCellFactory,
        private ExcelRowFactory   $excelRowFactory,
    ) {
    }

    /**
     * @param callable $rowRequirementsValidator
     * Callback that takes one argument (array of ExcelRow objects) and add some additional error messages if needed
     *
     * @return $this
     */
    public function setRowRequirementsValidator(callable $rowRequirementsValidator): static
    {
        $this->rowRequirementsValidator = $rowRequirementsValidator;

        return $this;
    }

    /**
     * @return array<int|string, ExcelRow> Keys are either EXCEL row numbers or model property values
     */
    public function getExcelRows(): array
    {
        return $this->excelRows;
    }

    public function getExcelRowsMetadata(): ExcelRowsMetadata
    {
        return $this->excelRowsMetadata;
    }

    /**
     * @return array<class-string<AbstractImportValidator>, string> Keys are validation class names, values are error messages
     */
    public function getImportRelateErrorMessages(): array
    {
        return $this->importRelateErrorMessages;
    }

    /**
     * @throws UnexpectedExcelCellClassException
     */
    protected abstract function configureExcelCells(): static;


    public function hasErrors(): bool
    {
        return
            !empty($this->importRelateErrorMessages) ||
            in_array(
                true,
                array_map(static function (ExcelRow $excelRow): bool {
                    return $excelRow->hasErrors();
                }, $this->excelRows)
            );
    }

    /**
     * @param string $separator
     *
     * @return string Error messages from all ExcelRows with value attached to each message
     */
    public function getMergedAllErrorMessages(string $separator = ', '): string
    {
        return implode(
            $separator,
            array_filter(
                array_merge(
                    $this->importRelateErrorMessages,
                    array_map(static function (ExcelRow $excelRow): string {
                        return $excelRow->getMergedAllErrorMessages(true);
                    }, $this->excelRows)
                )
            )
        );
    }

    /**
     * @param string $jsonExcelRows
     * @param string|null $indexBy Model property which values will be used as model keys
     * @param bool $namedColumnKeys TRUE if named column keys are used in model, FALSE otherwise
     * @param array $options Options passed to Excel cell class
     *
     * @return $this
     *
     * @throws InvalidNamedColumnKeyException
     * @throws JsonExcelRowsLoadException
     * @throws MissingExcelColumnsException
     * @throws MissingExcelFieldException
     * @throws UnexpectedExcelCellClassException
     */
    public function parseJson(
        string $jsonExcelRows,
        string $indexBy = null,
        bool   $namedColumnKeys = true,
        array  $options = [],
    ): static {
        $this->rawExcelRows = json_decode($jsonExcelRows, true);
        if (null === $this->rawExcelRows) {

            throw new JsonExcelRowsLoadException($jsonExcelRows);
        }
        $this
            ->castRawExcelRowsString()
            ->parseRawExcelRows(
                $namedColumnKeys ? self::FIRST_ROW_MODE_DONT_SKIP : self::FIRST_ROW_MODE_SKIP_IF_INVALID,
                $indexBy,
                $namedColumnKeys,
                $options
            );

        return $this;
    }

    /**
     * @param string $excelFilePath
     * @param string|null $indexBy Model property which values will be used as model keys
     * @param bool $namedColumnKeys TRUE if named column keys are used in model, FALSE otherwise
     * @param int $firstRowMode Defines what to do with the first EXCEL row. Possible values: <br>
     *                          AbstractExcelImporter::FIRST_ROW_MODE_SKIP <br>
     *                          AbstractExcelImporter::FIRST_ROW_MODE_DONT_SKIP <br>
     *                          AbstractExcelImporter::FIRST_ROW_MODE_SKIP_IF_INVALID <br>
     * @param array $options Options passed to Excel cell class
     *
     * @return $this
     *
     * @throws FileLoadException
     * @throws InvalidNamedColumnKeyException
     * @throws MissingExcelColumnsException
     * @throws MissingExcelFieldException
     * @throws UnexpectedExcelCellClassException
     */
    public function parseExcelFile(
        string                                              $excelFilePath,
        string                                              $indexBy = null,
        bool                                                $namedColumnKeys = true,
        #[ExpectedValues(valuesFromClass: self::class)] int $firstRowMode = self::FIRST_ROW_MODE_SKIP,
        array                                               $options = [],
    ): static
    {
        try {
            $sheet = IOFactory::load($excelFilePath)->getActiveSheet();
            $this->rawExcelRows = $sheet->toArray('', true, true, true);
        } catch (Throwable $exception) {

            throw new FileLoadException($excelFilePath, $exception);
        }

        $this
            ->castRawExcelRowsString()
            ->parseRawExcelRows($firstRowMode, $indexBy, $namedColumnKeys, $options)
        ;

        return $this;
    }

    public function getExcelRowsAsJson(): string
    {
        return json_encode($this->rawExcelRows);
    }

    /**
     * @throws UnexpectedExcelCellClassException
     * @throws MissingExcelColumnsException
     * @throws MissingExcelFieldException
     * @throws InvalidNamedColumnKeyException
     */
    protected function parseRawExcelRows(
        int     $firstRowMode,
        ?string $indexBy,
        bool    $namedColumnKeys,
        array   $options,
    ): void {
        $this
            ->configureExcelCells()
            ->resolvePreHeaderFieldMappedRows()
        ;
        if ($namedColumnKeys) {
            $this
                ->getColumnKeyNameExcelColumnKeyMappings()
                ->filterPreHeaderRows()
                ->transformExcelCellConfigurationsKeys()
                ->transformIndexByColumnKey()
            ;
        } else {
            $this->validateColumnIdentifiers();
        }
        $this->filterEmptyExcelRows();

        $firstRowMode = null !== $this->columnKeyMappings ? self::FIRST_ROW_MODE_SKIP : $firstRowMode;

        [$initialColumnMappedExcelCells, $fieldMappedExcelCells] = $this->createInitialExcelCells($options);
        $skippedFirstRow = true;
        reset($this->rawExcelRows);
        foreach ($this->rawExcelRows as $rowKey => $rawCellValues) {
            $isFirstRow = key($this->rawExcelRows) === $rowKey;
            if (!empty($this->columnMappedExcelCellConfigurations) && $isFirstRow && ($firstRowMode & self::FIRST_ROW_MODE_SKIP)) {

                continue;
            }

            $excelRow = $this->excelRowFactory->createFromInitialExcelCellsAndRawCellValues(
                $initialColumnMappedExcelCells,
                $fieldMappedExcelCells,
                $rawCellValues
            );
            if ($isFirstRow && ($firstRowMode & self::FIRST_ROW_MODE_SKIP_IF_INVALID) && $excelRow->hasErrors()) {

                continue;
            }
            $skippedFirstRow = !$isFirstRow && $skippedFirstRow;

            $this->excelRows[$rowKey] = $excelRow;
            if (null !== $this->indexByColumnKey) {
                $this->modelIndexedExcelRows[$rawCellValues[$this->indexByColumnKey]] = $excelRow;
            } else {
                $this->modelIndexedExcelRows[$rowKey] = $excelRow;
            }
        }
        if (null !== $this->rowRequirementsValidator) {
            ($this->rowRequirementsValidator)($this->excelRows);
        }
        $this->prepareExcelRowsMetadata($skippedFirstRow);
    }

    /**
     * @param string $excelCellClass Excel cell class extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell
     * @param string $cellName Cell name in EXCEL file
     * @param string $columnIdentifier Column key in EXCEL file
     * @param bool $cellRequired Whether cell value is required in an EXCEL file
     * @param bool $isColumn Whether cell value is taken from column in current row. static field otherwise
     * @param AbstractCellValidator[] $cellValidators Validators extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator that will validate raw value
     *
     * @return AbstractExcelImporter
     *
     * @throws UnexpectedClassException
     */
    protected function addExcelCell(
        string $excelCellClass,
        string $cellName,
        string $columnIdentifier,
        bool   $cellRequired = true,
        bool   $isColumn = true,
        array $cellValidators = []
    ): static
    {
        $excelCellConfiguration = new ExcelCellConfiguration(
            $excelCellClass,
            $cellName,
            $cellRequired,
            $isColumn,
            $cellValidators
        );

        if (!$isColumn) {
            [$rowNumber, $columnKey] = $this->fieldIdResolver->resolveRowNumberColumnKey($columnIdentifier);
            $this->fieldMappedExcelCellConfigurations[$rowNumber][$columnKey] = $excelCellConfiguration;

            return $this;
        }

        $this->columnMappedExcelCellConfigurations[$columnIdentifier] = $excelCellConfiguration;

        return $this;
    }

    /**
     * @throws MissingExcelColumnsException
     * @throws InvalidNamedColumnKeyException
     */
    private function getColumnKeyNameExcelColumnKeyMappings(): static
    {
        $columnKeys = array_map('strtolower', array_keys($this->columnMappedExcelCellConfigurations));

        $headerRow = [];
        $missingColumnKeys = $columnKeys;
        $missingColumnsCount = !empty($this->rawExcelRows) ? count(current($this->rawExcelRows)) : 0;
        foreach ($this->rawExcelRows as $rowNumber => $excelCellValues) {
            $currentMissingColumnKeys = array_udiff($columnKeys, $excelCellValues, 'strcasecmp');
            if (empty($currentMissingColumnKeys)) {
                $missingColumnKeys = [];
                $this->headerRowIndex = $rowNumber;
                $headerRow = array_map('strtolower', $excelCellValues);

                break;
            }
            $currentMissingColumnsCount = count($currentMissingColumnKeys);
            if ($missingColumnsCount > $currentMissingColumnsCount) {
                $missingColumnKeys = $currentMissingColumnKeys;
                $missingColumnsCount = $currentMissingColumnsCount;
            }
        }
        if (!empty($missingColumnKeys)) {

            throw new MissingExcelColumnsException($missingColumnKeys);
        }

        $fieldIdLikeColumnKeys = array_filter($columnKeys, function (string $columnKey): bool {
            return $this->fieldIdResolver->isColumnKeyFieldIdentifier($columnKey);
        });
        if (!empty($fieldIdLikeColumnKeys)) {

            throw new InvalidNamedColumnKeyException(current($fieldIdLikeColumnKeys));
        }

        $this->columnKeyMappings = array_flip(array_uintersect(
            $headerRow,
            array_keys($this->columnMappedExcelCellConfigurations),
            'strcasecmp'
        ));

        return $this;
    }

    private function resolvePreHeaderFieldMappedRows(): void
    {
        $this->fieldMappedPreHeaderRows = array_intersect_key($this->rawExcelRows, $this->fieldMappedExcelCellConfigurations);
    }

    private function filterPreHeaderRows(): static
    {
        $headerRowOffset = (int)array_search($this->headerRowIndex, array_keys($this->rawExcelRows));
        $this->rawExcelRows =
            array_slice($this->rawExcelRows, $headerRowOffset, null, true) + $this->fieldMappedPreHeaderRows;
        ksort($this->rawExcelRows);

        return $this;
    }

    private function transformExcelCellConfigurationsKeys(): static
    {
        $keyLoweredExcelCellConfigurations = array_change_key_case($this->columnMappedExcelCellConfigurations);
        foreach ($this->columnKeyMappings ?? [] as $columnKeyName => $excelColumnKey) {
            $this->columnMappedExcelCellConfigurations[$excelColumnKey] = $keyLoweredExcelCellConfigurations[$columnKeyName];
        }
        $this->columnMappedExcelCellConfigurations = array_diff_ukey(
            $this->columnMappedExcelCellConfigurations,
            $keyLoweredExcelCellConfigurations,
            'strcasecmp'
        );

        return $this;
    }

    private function transformIndexByColumnKey(): void
    {
        if (null === $this->indexByColumnKey) {

            return;
        }

        $this->indexByColumnKey = $this->columnKeyMappings[strtolower($this->indexByColumnKey)];
    }

    /**
     * @throws MissingExcelColumnsException
     * @throws MissingExcelFieldException
     *
     * @noinspection PhpUnnecessaryCurlyVarSyntaxInspection
     */
    private function validateColumnIdentifiers(): void
    {
        foreach ($this->fieldMappedExcelCellConfigurations as $rowNumber => $excelCellConfigurations) {
            foreach ($excelCellConfigurations as $columnKey => $excelCellConfiguration) {
                if (null !== ($this->rawExcelRows[$rowNumber][$columnKey])) {

                    continue;
                }

                throw new MissingExcelFieldException("{$columnKey}{$rowNumber}");
            }
        }

        $missingColumnKeys = array_diff_key($this->columnMappedExcelCellConfigurations, current($this->rawExcelRows) ?: []);
        if (empty($missingColumnKeys)) {

            return;
        }

        throw new MissingExcelColumnsException(array_keys($missingColumnKeys));
    }

    private function prepareExcelRowsMetadata(bool $skippedFirstRow): void
    {
        $this->excelRowsMetadata = ExcelRowsMetadataFactory::createFromExcelImporterData($this->rawExcelRows, $this->headerRowIndex, $this->columnKeyMappings, $skippedFirstRow);
    }

    /**
     * Create ExcelCell without value (To avoid re-calling of dictionary setups which are the same for all rows).
     *
     * @return array{array<string, AbstractExcelCell>, array<int, array<string, AbstractExcelCell>>}
     *         column mapped EXCEL cell skeletons and ready EXCEL cells for field mapped EXCEL cells
     *
     * @noinspection PhpUnnecessaryCurlyVarSyntaxInspection
     */
    private function createInitialExcelCells(array $options): array
    {
        $initialColumnMappedExcelCells = [];
        foreach ($this->columnMappedExcelCellConfigurations as $columnKey => $excelCellConfiguration) {
            $initialColumnMappedExcelCells[$columnKey] = $this->excelCellFactory->makeSkeletonFromConfiguration($excelCellConfiguration, $options);
        }
        $fieldMappedExcelCells = [];
        foreach ($this->fieldMappedExcelCellConfigurations as $rowNumber => $excelCellConfigurations) {
            foreach ($excelCellConfigurations as $columnKey => $excelCellConfiguration) {
                $fieldMappedExcelCells["{$columnKey}{$rowNumber}"] = $this->excelCellFactory
                    ->makeSkeletonFromConfiguration($excelCellConfiguration, $options)
                    ->setRawValue($this->rawExcelRows[$rowNumber][$columnKey]);
            }
        }

        return [$initialColumnMappedExcelCells, $fieldMappedExcelCells];
    }

    private function castRawExcelRowsString(): static
    {
        $this->rawExcelRows = array_map(static function (array $rawCellValues): array {
            return array_map(static function ($rawCellValue): string {
                return trim((string)$rawCellValue);
            }, $rawCellValues);
        }, $this->rawExcelRows);

        return $this;
    }

    private function filterEmptyExcelRows(): void
    {
        $this->rawExcelRows = array_filter($this->rawExcelRows, function (array $excelRow): bool {
            return !empty(array_filter(array_intersect_key($excelRow, $this->columnMappedExcelCellConfigurations)));
        }) + $this->fieldMappedPreHeaderRows;
        ksort($this->rawExcelRows);
    }

}