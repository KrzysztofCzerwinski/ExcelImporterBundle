<?php
/** @noinspection PhpPropertyOnlyWrittenInspection */
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use Kczer\ExcelImporterBundle\Exception\Exporter\ReverseExcelCell\ValueNotStringReversableException;
use Throwable;

class ReverseExcelCell
{
    private string $baseExcelCellClass;

    public function setBaseExcelCellClass(string $baseExcelCellClass): static
    {
        $this->baseExcelCellClass = $baseExcelCellClass;

        return $this;
    }

    /**
     * @throws ValueNotStringReversableException
     */
    public function getReversedExcelCellValue(mixed $value): string
    {
        try {
            return (string)$value;
        } catch (Throwable $exception) {

            throw new ValueNotStringReversableException($this->baseExcelCellClass, $value);
        }
    }
}
