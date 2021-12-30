<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\Factory;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;

class ExcelRowFactory
{
    /**
     * @param AbstractExcelCell[] $skeletonExcelCells
     * @param string[] $rawCellValues
     *
     * @return ExcelRow
     */
    public function createFromExcelCellSkeletonsAndRawCellValues(array $skeletonExcelCells, array $rawCellValues): ExcelRow
    {
        /** @var AbstractExcelCell[] $excelCells */
        $excelCells = [];
        foreach ($skeletonExcelCells as $columnKey => $skeletonExcelCell) {
            $excelCells[$columnKey] = (clone $skeletonExcelCell)->setRawValue($rawCellValues[$columnKey]);
        }

        return (new ExcelRow())->setExcelCells($excelCells);
    }
}