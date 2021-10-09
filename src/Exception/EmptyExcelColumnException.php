<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Exception;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Throwable;
use function get_class;

class EmptyExcelColumnException extends Exception
{
    public function __construct(AbstractExcelCell $excelCell, string $columnKey, Throwable $previous = null)
    {
        parent::__construct(
            sprintf("Empty column '%s' of key '%s', expected %s compatible values. Make sure there is no misspelling in header column names", $excelCell->getName(), $columnKey, get_class($excelCell)),
            0,
            $previous
        );
    }
}