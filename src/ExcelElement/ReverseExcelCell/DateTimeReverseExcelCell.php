<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use DateTime;

class DateTimeReverseExcelCell extends ReverseExcelCell
{
    /** @var string */
    private $reversedFormat = 'Y-m-d';

    public function getReversedFormat(): string
    {
        return $this->reversedFormat;
    }

    public function setReversedFormat(string $reversedFormat): self
    {
        $this->reversedFormat = $reversedFormat;
        return $this;
    }


    protected function getReversedExcelCellValue(): string
    {
        /** @var DateTime $value */
        $value = $this->getValue();

        return $value->format($this->reversedFormat);
    }
}