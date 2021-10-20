<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use DateTime;

class DateTimeReverseExcelCell extends ReverseExcelCell
{
    /** @var string */
    private $reversedFormat;

    public function setReversedFormat(?string $reversedFormat): self
    {
        $this->reversedFormat = $reversedFormat;

        return $this;
    }

    /**
     * @param DateTime|null $value
     *
     * @return string
     */
    public function getReversedExcelCellValue($value): string
    {
        return null !== $value ? $value->format($this->reversedFormat ?? 'd.m.Y') : '';
    }
}