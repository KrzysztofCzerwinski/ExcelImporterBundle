<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration\ExcelCellConfiguration;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelCellFactory;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelRowFactory;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelFileLoadException;
use Kczer\ExcelImporterBundle\Exception\EmptyExcelColumnException;
use Kczer\ExcelImporterBundle\Exception\JsonExcelRowsLoadException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use function array_filter;
use function array_flip;
use function array_key_first;
use function array_keys;
use function array_map;
use function array_slice;
use function array_unshift;
use function array_values;
use function json_decode;
use function json_encode;
use function trim;

abstract class AbstractExcelImporter
{
    public const FIRST_ROW_MODE_SKIP = 1;

    public const FIRST_ROW_MODE_DONT_SKIP = 2;

    public const FIRST_ROW_MODE_SKIP_IF_INVALID = 4;


    /** @var TranslatorInterface */
    protected $translator;

    /** @var string[]|null Array with keys as human-readable column keys and values as EXCEL column keys with A-Z notation. Null if no mapping is required */
    protected $columnKeyMappings = null;

    /** @var array */
    private $rawExcelRows = [];

    /** @var ?callable */
    private $rowRequirementsValidator = null;

    /** @var ExcelCellFactory */
    private $excelCellFactory;

    /** @var ExcelRowFactory */
    private $excelRowFactory;

    /** @var ExcelCellConfiguration[] */
    private $excelCellConfigurations = [];

    /** @var ExcelRow[] */
    private $excelRows = [];

    /** @var int|null */
    private $headerRowIndex;

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

    public function getHeaderRowIndex(): ?int
    {
        return $this->headerRowIndex;
    }

    /**
     * @return ExcelCellConfiguration[]
     */
    protected function getExcelCellConfigurations(): array
    {
        return $this->excelCellConfigurations;
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
     * @throws EmptyExcelColumnException
     * @throws JsonExcelRowsLoadException
     * @throws UnexpectedExcelCellClassException
     */
    public function parseJson(string $jsonExcelRows): self
    {
        $this->rawExcelRows = json_decode($jsonExcelRows, true);
        if (null === $this->rawExcelRows) {

            throw new JsonExcelRowsLoadException($jsonExcelRows);
        }
        $this
            ->castRawExcelRowsString()
            ->parseRawExcelRows(self::FIRST_ROW_MODE_DONT_SKIP);

        return $this;
    }

    /**
     * @param string $excelFilePath
     * @param int $firstRowMode DEFINES what to do with the first EXCEL row. Possible values: <br>
     * FIRST_ROW_MODE_SKIP <br>
     * FIRST_ROW_MODE_DONT_SKIP <br>
     * FIRST_ROW_MODE_DONT_SKIP_IF_INVALID <br>
     *
     * @return $this
     *
     * @throws ExcelFileLoadException
     * @throws EmptyExcelColumnException
     * @throws UnexpectedExcelCellClassException
     */
    public function parseExcelFile(string $excelFilePath, int $firstRowMode = self::FIRST_ROW_MODE_SKIP): self
    {
        try {
            $sheet = IOFactory::load($excelFilePath)->getActiveSheet();
            $this->rawExcelRows = array_values($sheet->toArray('', true, true, true));
        } catch (Throwable $exception) {

            throw new ExcelFileLoadException($excelFilePath, $exception);
        }

        $this
            ->castRawExcelRowsString()
            ->parseRawExcelRows($firstRowMode);

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
     * @throws EmptyExcelColumnException
     * @throws UnexpectedExcelCellClassException
     */
    protected function parseRawExcelRows(int $firstRowMode): void
    {
        $this
            ->configureExcelCells()
            ->determineFirstColumnKeyMatchingRowIndex()
            ->getColumnKeyNameExcelColumnKeyMappings()
            ->filterPreHeaderRows()
            ->filterEmptyExcelRows()
            ->transformExcelCellConfigurationsKeysIfRequired();

        $firstRowMode = null !== $this->columnKeyMappings ? self::FIRST_ROW_MODE_SKIP : $firstRowMode;

        $skeletonExcelCells = $this->createSkeletonExcelCells();

        foreach ($this->rawExcelRows as $rowKey => $rawCellValues) {
            $isFirstRow = array_key_first($this->rawExcelRows) === $rowKey;
            if ($isFirstRow && ($firstRowMode & self::FIRST_ROW_MODE_SKIP)) {

                continue;
            }

            $excelRow = $this->excelRowFactory->createFromExcelCellSkeletonsAndRawCellValues($skeletonExcelCells, $rawCellValues);
            if ($isFirstRow && ($firstRowMode & self::FIRST_ROW_MODE_SKIP_IF_INVALID) && $excelRow->hasErrors()) {

                continue;
            }

            $this->excelRows[] = $excelRow;
        }
        if (null !== $this->rowRequirementsValidator) {
            ($this->rowRequirementsValidator)($this->excelRows);
        }
        $this->excelRows = array_values($this->excelRows);
    }

    /**
     * @param string $excelCellClass Excel cell class extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell
     * @param string $cellName Cell name in EXCEL file
     * @param string $columnKey Column key in EXCEL file
     * @param bool $cellRequired Whether cell value is required in an EXCEL file
     * @param AbstractValidator[] $validators Validators extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator that will validate raw value
     *
     * @return AbstractExcelImporter
     *
     * @throws UnexpectedClassException
     */
    protected function addExcelCell(string $excelCellClass, string $cellName, string $columnKey, bool $cellRequired = true, array $validators = []): self
    {
        $this->excelCellConfigurations[$this->translator->trans($columnKey)] = new ExcelCellConfiguration($excelCellClass, $cellName, $cellRequired, $validators);

        return $this;
    }

    private function determineFirstColumnKeyMatchingRowIndex(): self
    {
        $columnKeyNames = array_keys($this->getExcelCellConfigurations());
        foreach ($this->rawExcelRows as $index => $rawExcelCellValues) {
            if (!empty(array_diff($columnKeyNames, $rawExcelCellValues))) {

                continue;
            }
            $this->headerRowIndex = $index;

            return $this;
        }

        return $this;
    }

    private function getColumnKeyNameExcelColumnKeyMappings(): self
    {
        if (null === $this->headerRowIndex) {

            return $this;
        }
        $this->columnKeyMappings = array_flip(array_intersect($this->rawExcelRows[$this->headerRowIndex], array_keys($this->getExcelCellConfigurations())));

        return $this;
    }

    private function filterPreHeaderRows(): self
    {
        $this->rawExcelRows = array_slice($this->rawExcelRows, $this->headerRowIndex ?? 0);

        return $this;
    }

    private function transformExcelCellConfigurationsKeysIfRequired(): void
    {
        foreach ($this->columnKeyMappings ?? [] as $columnKeyName => $excelColumnKey) {
            $this->excelCellConfigurations[$excelColumnKey] = $this->excelCellConfigurations[$columnKeyName];

            unset($this->excelCellConfigurations[$columnKeyName]);
        }
    }

    /**
     * Create ExcelCell without value (To avoid re-calling of dictionary setups which are the same for all rows).
     *
     * @return AbstractExcelCell[]
     */
    private function createSkeletonExcelCells(): array
    {
        $initialExcelCells = [];
        foreach ($this->getExcelCellConfigurations() as $columnKey => $excelCellConfiguration) {
            $initialExcelCells[$columnKey] = $this->excelCellFactory->makeSkeletonFromConfiguration($excelCellConfiguration);
        }

        return $initialExcelCells;
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


    private function filterEmptyExcelRows(): self
    {
        $this->rawExcelRows = array_values(
            array_filter($this->rawExcelRows, static function (array $excelRow): bool {
                return !empty(array_filter($excelRow));
            })
        );

        return $this;
    }

}