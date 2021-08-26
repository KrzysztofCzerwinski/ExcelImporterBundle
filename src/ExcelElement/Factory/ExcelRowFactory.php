<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\Factory;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;
use Kczer\ExcelImporterBundle\Exception\EmptyExcelColumnException;
use function key_exists;

class ExcelRowFactory
{
    /**
     * @param AbstractExcelCell[] $skeletonExcelCells
     * @param string[] $rawCellValues
     *
     * @return ExcelRow
     *
     * @throws EmptyExcelColumnException
     */
    public function createFromExcelCellSkeletonsAndRawCellValues(array $skeletonExcelCells, array $rawCellValues): ExcelRow
    {
        /** @var AbstractExcelCell[] $excelCells */
        $excelCells = [];
        foreach ($skeletonExcelCells as $columnKey => $skeletonExcelCell) {
            if (!key_exists($columnKey, $rawCellValues)) {

                throw new EmptyExcelColumnException($skeletonExcelCell, $columnKey);
            }
            $excelCells[$columnKey] = (clone $skeletonExcelCell)->setRawValue($rawCellValues[$columnKey]);
        }

        return (new ExcelRow())->setExcelCells($excelCells);
    }
}