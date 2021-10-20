<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use Kczer\ExcelImporterBundle\Exception\Exporter\ReverseExcelCell\ValueNotStringReversableException;
use Throwable;

class ReverseExcelCell
{
    /** @var string */
    private $baseExcelCellClass;

    public function setBaseExcelCellClass(string $baseExcelCellClass): ReverseExcelCell
    {
        $this->baseExcelCellClass = $baseExcelCellClass;
        return $this;
    }

    /**
     * @param mixed $value
     *
     * @return string
     *
     * @throws ValueNotStringReversableException
     */
    public function getReversedExcelCellValue($value): string
    {
        try {
            return (string)$value;
        } catch (Throwable $exception) {

            throw new ValueNotStringReversableException($this->baseExcelCellClass, $value);
        }
    }
}