<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration\ExcelCellConfiguration;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelCellFactory;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelRowFactory;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidExcelFieldIdException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\FileLoadException;
use Kczer\ExcelImporterBundle\Exception\InvalidNamedColumnKeyException;
use Kczer\ExcelImporterBundle\Exception\JsonExcelRowsLoadException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelColumnsException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelFieldException;
use Kczer\ExcelImporterBundle\Model\ExcelRowsMetadata;
use Kczer\ExcelImporterBundle\Model\Factory\ExcelRowsMetadataFactory;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use function array_filter;
use function array_flip;
use function array_keys;
use function array_map;
use function array_search;
use function array_slice;
use function array_udiff;
use function array_uintersect;
use function array_unshift;
use function current;
use function implode;
use function json_decode;
use function json_encode;
use function key;
use function key_exists;
use function preg_match;
use function reset;
use function trim;

abstract class AbstractExcelImporter
{
    public const FIRST_ROW_MODE_SKIP = 1;

    public const FIRST_ROW_MODE_DONT_SKIP = 2;

    public const FIRST_ROW_MODE_SKIP_IF_INVALID = 4;


    /** @var string[]|null Array with keys as human-readable column keys and values as EXCEL column keys with A-Z notation. Null if no mapping is required */
    protected $columnKeyMappings = null;

    /** @var array */
    private $rawExcelRows = [];

    /** @var array<string, ExcelCellConfiguration> */
    private $columnMappedExcelCellConfigurations = [];

    /**
     * @var array<int, array<string, ExcelCellConfiguration>>
     *      Keys are row number, and then column key
     */
    private $fieldMappedExcelCellConfigurations = [];

    /** @var ExcelRow[] */
    private $excelRows = [];

    /** @var int|null */
    private $headerRowIndex;

    /** @var ExcelRowsMetadata */
    private $excelRowsMetadata;


    /** @var ?callable */
    private $rowRequirementsValidator = null;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var ExcelCellFactory */
    private $excelCellFactory;

    /** @var ExcelRowFactory */
    private $excelRowFactory;


    public function __construct(
        TranslatorInterface $translator,
        ExcelCellFactory    $excelCellFactory,
        ExcelRowFactory     $excelRowFactory
    )
    {
        $this->translator = $translator;
        $this->excelCellFactory = $excelCellFactory;
        $this->excelRowFactory = $excelRowFactory;
    }

    /**
     * @param callable $rowRequirementsValidator
     * Callback that takes one argument (array of ExcelRow objects) and add some additional error messages if needed
     *
     * @return $this
     */
    public function setRowRequirementsValidator(callable $rowRequirementsValidator): self
    {
        $this->rowRequirementsValidator = $rowRequirementsValidator;

        return $this;
    }

    /**
     * @return ExcelRow[]
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
     * @throws UnexpectedExcelCellClassException
     */
    protected abstract function configureExcelCells(): self;


    public function hasErrors(): bool
    {
        return in_array(
            true,
            array_map(static function (ExcelRow $excelCell): bool {
                return $excelCell->hasErrors();
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
                array_map(static function (ExcelRow $excelRow): string {
                    return $excelRow->getMergedAllErrorMessages(true);
                }, $this->excelRows)
            )
        );
    }

    /**
     * @param string $jsonExcelRows
     * @param bool $namedColumnKeys TRUE if named column keys are used in model, FALSE otherwise
     *
     * @return $this
     *
     * @throws JsonExcelRowsLoadException
     * @throws UnexpectedExcelCellClassException
     * @throws MissingExcelColumnsException
     */
    public function parseJson(string $jsonExcelRows, bool $namedColumnKeys = true): self
    {
        $this->rawExcelRows = json_decode($jsonExcelRows, true);
        if (null === $this->rawExcelRows) {

            throw new JsonExcelRowsLoadException($jsonExcelRows);
        }
        $this
            ->castRawExcelRowsString()
            ->parseRawExcelRows(self::FIRST_ROW_MODE_DONT_SKIP, $namedColumnKeys);

        return $this;
    }

    /**
     * @param string $excelFilePath
     * @param bool $namedColumnKeys TRUE if named column keys are used in model, FALSE otherwise
     * @param int $firstRowMode DEFINES what to do with the first EXCEL row. Possible values: <br>
     *                          AbstractExcelImporter::FIRST_ROW_MODE_SKIP <br>
     *                          AbstractExcelImporter::FIRST_ROW_MODE_DONT_SKIP <br>
     *                          AbstractExcelImporter::FIRST_ROW_MODE_SKIP_IF_INVALID <br>
     *
     * @return $this
     *
     * @throws FileLoadException
     * @throws UnexpectedExcelCellClassException
     * @throws MissingExcelColumnsException
     */
    public function parseExcelFile(string $excelFilePath, bool $namedColumnKeys = true, int $firstRowMode = self::FIRST_ROW_MODE_SKIP): self
    {
        try {
            $sheet = IOFactory::load($excelFilePath)->getActiveSheet();
            $this->rawExcelRows = $sheet->toArray('', true, true, true);
        } catch (Throwable $exception) {

            throw new FileLoadException($excelFilePath, $exception);
        }

        $this
            ->castRawExcelRowsString()
            ->parseRawExcelRows($firstRowMode, $namedColumnKeys);

        return $this;
    }

    public function getExcelRowsAsJson(): string
    {
        $excelRows = array_map(static function (ExcelRow $excelRow): array {
            return $excelRow->toArray();
        }, $this->excelRows);

        if (null !== $this->columnKeyMappings) {
            array_unshift($excelRows, array_flip($this->columnKeyMappings));
        }

        return json_encode($excelRows);
    }

    /**
     * @throws UnexpectedExcelCellClassException
     * @throws MissingExcelColumnsException
     * @throws MissingExcelFieldException
     * @throws InvalidNamedColumnKeyException
     */
    protected function parseRawExcelRows(int $firstRowMode, bool $namedColumnKeys): void
    {
        $this->configureExcelCells();
        if ($namedColumnKeys) {
            $this
                ->getColumnKeyNameExcelColumnKeyMappings()
                ->filterPreHeaderRows()
                ->transformExcelCellConfigurationsKeys()
            ;
        } else {
            $this->validateColumnIdentifiers();
        }
        $this->filterEmptyExcelRows();

        $firstRowMode = null !== $this->columnKeyMappings ? self::FIRST_ROW_MODE_SKIP : $firstRowMode;

        [$initialColumnMappedExcelCells, $fieldMappedExcelCells] = $this->createInitialExcelCells();

        $skippedFirstRow = true;
        reset($this->rawExcelRows);
        foreach ($this->rawExcelRows as $rowKey => $rawCellValues) {
            $isFirstRow = key($this->rawExcelRows) === $rowKey;
            if ($isFirstRow && ($firstRowMode & self::FIRST_ROW_MODE_SKIP)) {

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

            $this->excelRows[] = $excelRow;
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
     * @param AbstractValidator[] $validators Validators extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator that will validate raw value
     *
     * @return AbstractExcelImporter
     *
     * @throws UnexpectedClassException
     * @throws InvalidExcelFieldIdException
     */
    protected function addExcelCell(
        string $excelCellClass,
        string $cellName,
        string $columnIdentifier,
        bool   $cellRequired = true,
        bool   $isColumn = true,
        array  $validators = []
    ): self
    {
        $excelCellConfiguration = new ExcelCellConfiguration(
            $excelCellClass,
            $cellName,
            $cellRequired,
            $isColumn,
            $validators
        );

        $columnIdentifier = $this->translator->trans($columnIdentifier);
        if (!$isColumn) {
            [$rowNumber, $columnKey] = $this->resolveRowNumberColumnKeyFromFieldId($columnIdentifier);
            $this->fieldMappedExcelCellConfigurations[$rowNumber][$columnKey] = $excelCellConfiguration;

            return $this;
        }

        $this->columnMappedExcelCellConfigurations[$columnIdentifier] = $excelCellConfiguration;

        return $this;
    }

    /**
     * @return array{int, string} row number and column key correspondingly
     *
     * @throws InvalidExcelFieldIdException
     */
    private function resolveRowNumberColumnKeyFromFieldId(string $fieldId): array
    {
        /** @example A1 | C 10 | abc23 */
        if (1 !== preg_match('|^([A-Z]+)\s*(\d+)$|i', trim($fieldId), $matches)) {

            throw new InvalidExcelFieldIdException($fieldId);
        }
        [, $columnKey, $rowNumber] = $matches;

        return [(int)$rowNumber, $columnKey];
    }

    /**
     * @throws MissingExcelColumnsException
     * @throws InvalidNamedColumnKeyException
     */
    private function getColumnKeyNameExcelColumnKeyMappings(): self
    {
        $headerRow = $this->getFirstNonEmptyExcelRow();
        $columnKeys = array_keys($this->columnMappedExcelCellConfigurations);
        $missingColumnKeys = array_udiff($columnKeys, $headerRow, 'strcasecmp');
        if (!empty($missingColumnKeys)) {

            throw new MissingExcelColumnsException(array_map([$this->translator, 'trans'], $missingColumnKeys));
        }

        $fieldIdLikeColumnKeys = array_filter($columnKeys, static function (string $columnKey): bool {
            return 1 === preg_match('|[A-Z]+\s*\d+|i', $columnKey);
        });
        if (!empty($fieldIdLikeColumnKeys)) {

            throw new InvalidNamedColumnKeyException(current($fieldIdLikeColumnKeys));
        }

        $this->headerRowIndex = key($this->rawExcelRows);
        $this->columnKeyMappings = array_flip(array_uintersect($headerRow, array_keys($this->columnMappedExcelCellConfigurations), 'strcasecmp'));

        return $this;
    }

    private function filterPreHeaderRows(): self
    {
        $fieldMappedHeaderRows = array_intersect_key($this->rawExcelRows, $this->fieldMappedExcelCellConfigurations);

        $headerRowOffset = (int)array_search($this->headerRowIndex, array_keys($this->rawExcelRows));
        $this->rawExcelRows =
            array_slice($this->rawExcelRows, $headerRowOffset, null, true) + $fieldMappedHeaderRows;

        return $this;
    }

    private function transformExcelCellConfigurationsKeys(): void
    {
        foreach ($this->columnKeyMappings ?? [] as $columnKeyName => $excelColumnKey) {
            $this->columnMappedExcelCellConfigurations[$excelColumnKey] = $this->columnMappedExcelCellConfigurations[$columnKeyName];

            unset($this->columnMappedExcelCellConfigurations[$columnKeyName]);
        }
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

        throw new MissingExcelColumnsException(array_map([$this->translator, 'trans'], array_keys($missingColumnKeys)));
    }

    private function getFirstNonEmptyExcelRow(): array
    {
        return current(
            array_filter($this->rawExcelRows, static function (array $excelRow): bool {
                return !empty(array_filter($excelRow));
            })
        ) ?: [];
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
    private function createInitialExcelCells(): array
    {
        $initialColumnMappedExcelCells = [];
        foreach ($this->columnMappedExcelCellConfigurations as $columnKey => $excelCellConfiguration) {
            $initialColumnMappedExcelCells[$columnKey] = $this->excelCellFactory->makeSkeletonFromConfiguration($excelCellConfiguration);
        }
        $fieldMappedExcelCells = [];
        foreach ($this->fieldMappedExcelCellConfigurations as $rowNumber => $excelCellConfigurations) {
            foreach ($excelCellConfigurations as $columnKey => $excelCellConfiguration) {
                $fieldMappedExcelCells["{$columnKey}{$rowNumber}"] = $this->excelCellFactory
                    ->makeSkeletonFromConfiguration($excelCellConfiguration)
                    ->setRawValue($this->rawExcelRows[$rowNumber][$columnKey]);
            }
        }

        return [$initialColumnMappedExcelCells, $fieldMappedExcelCells];
    }

    private function castRawExcelRowsString(): self
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
        });
    }

}