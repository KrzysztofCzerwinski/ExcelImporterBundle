<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use DateTime;

class DateTimeReverseExcelCell extends AbstractReverseExcelCell
{
    /** @var string */
    private $reversedFormat = 'Y-m-d';

    public function getReversedFormat(): string
    {
        return $this->reversedFormat;
    }

    public function setReversedFormat(string $reversedFormat): DateTimeReverseExcelCell
    {
        $this->reversedFormat = $reversedFormat;
        return $this;
    }


    public function getReversedExcelCellValue(): string
    {
        /** @var DateTime $value */
        $value = $this->getValue();

        return $value->format($this->reversedFormat);
    }
}