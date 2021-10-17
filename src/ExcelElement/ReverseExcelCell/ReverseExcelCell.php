<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

class ReverseExcelCell
{
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function getReversedExcelCellValue($value): string
    {
        return (string)$value;
    }
}