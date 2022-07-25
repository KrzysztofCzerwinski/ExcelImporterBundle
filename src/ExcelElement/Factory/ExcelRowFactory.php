<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\Factory;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;

class ExcelRowFactory
{
    /**
     * @param array<string, AbstractExcelCell> $columnMappedInitialExcelCells
     * @param array<string, AbstractExcelCell> $fieldMappedExcelCells
     * @param string[] $rawCellValues
     *
     * @return ExcelRow
     */
    public function createFromInitialExcelCellsAndRawCellValues(
        array $columnMappedInitialExcelCells,
        array $fieldMappedExcelCells,
        array $rawCellValues,
    ): ExcelRow
    {
        /** @var AbstractExcelCell[] $excelCells */
        $excelCells = [];
        foreach ($columnMappedInitialExcelCells as $columnKey => $skeletonExcelCell) {
            $excelCells[$columnKey] = (clone $skeletonExcelCell)->setRawValue($rawCellValues[$columnKey]);
        }

        return (new ExcelRow())->setExcelCells($excelCells + $fieldMappedExcelCells);
    }
}