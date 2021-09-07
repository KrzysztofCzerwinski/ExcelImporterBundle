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
use Throwable;
use function array_filter;
use function array_map;
use function array_values;
use function json_decode;
use function json_encode;
use function key;

abstract class AbstractExcelImporter
{
    public const FIRST_ROW_MODE_SKIP = 1;

    public const FIRST_ROW_MODE_DONT_SKIP = 2;

    public const FIRST_ROW_MODE_DONT_SKIP_IF_INVALID = 4;


    /** @var ?callable */
    private $rowRequirementsValidator = null;

    /** @var ExcelCellFactory */
    private $excelCellFactory;

    /** @var ExcelRowFactory */
    private $excelRowFactory;

    /** @var ExcelCellConfiguration[] */
    private $excelCellConfigurations;

    /** @var ExcelRow[] */
    private $excelRows;

    public function __construct(ExcelCellFactory $excelCellFactory, ExcelRowFactory $excelRowFactory)
    {
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
    protected abstract function configureExcelCells(): void;


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
        $rawExcelRows = json_decode($jsonExcelRows, true);
        if (null === $rawExcelRows) {

            throw new JsonExcelRowsLoadException($jsonExcelRows);
        }
        $this->parseRawExcelRows($rawExcelRows, self::FIRST_ROW_MODE_DONT_SKIP);

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
     * @throws EmptyExcelColumnException
     * @throws ExcelFileLoadException
     * @throws UnexpectedExcelCellClassException
     */
    public function parseExcelFile(string $excelFilePath, int $firstRowMode = self::FIRST_ROW_MODE_SKIP): self
    {
        try {
            $sheet = IOFactory::load($excelFilePath)->getActiveSheet();
            $rawExcelRows = $sheet->toArray('', true, true, true);
        } catch (Throwable $exception) {

            throw new ExcelFileLoadException($excelFilePath, $exception);
        }
        $this->parseRawExcelRows($rawExcelRows, $firstRowMode);

        return $this;
    }

    public function getExcelRowsAsJson(): string
    {
        return json_encode(
            array_map(static function (ExcelRow $excelRow): array {
                return $excelRow->toArray();
            }, $this->excelRows)
        );
    }

    /**
     * @throws EmptyExcelColumnException
     * @throws UnexpectedExcelCellClassException
     */
    protected function parseRawExcelRows(array $rawExcelRows, int $firstRowMode): void
    {
        $this->configureExcelCells();
        $skeletonExcelCells = $this->createSkeletonExcelCells();

        foreach ($rawExcelRows as $rowKey => $rawCellValues) {
            $isFirstRow = key($rawExcelRows) === $rowKey;
            if (
                ($isFirstRow && ($firstRowMode & self::FIRST_ROW_MODE_SKIP)) ||
                $this->areAllRawExcelCellValuesEmpty($rawCellValues)
            ) {

                continue;
            }

            $excelRow = $this->excelRowFactory->createFromExcelCellSkeletonsAndRawCellValues($skeletonExcelCells, $this->parseRawCellValuesString($rawCellValues));
            if ($isFirstRow && ($firstRowMode & self::FIRST_ROW_MODE_DONT_SKIP_IF_INVALID) && $excelRow->hasErrors()) {

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
        $this->excelCellConfigurations[$columnKey] = new ExcelCellConfiguration($excelCellClass, $cellName, $cellRequired, $validators);

        return $this;
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

    /**
     * @param array $rawCellValues
     *
     * @return string[]
     */
    private function parseRawCellValuesString(array $rawCellValues): array
    {
        return array_map('strval', $rawCellValues);
    }

    /**
     * @param string[] $rawExcelCellValues
     *
     * @return bool
     */
    private function areAllRawExcelCellValuesEmpty(array $rawExcelCellValues): bool
    {
        return empty(array_filter($rawExcelCellValues));
    }

}