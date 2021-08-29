<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Throwable;

class UnexpectedExcelCellClassException extends UnexpectedClassException
{
    public function __construct(string $givenClass, Throwable $previous = null)
    {
        parent::__construct($givenClass, AbstractExcelCell::class, $previous);
    }
}