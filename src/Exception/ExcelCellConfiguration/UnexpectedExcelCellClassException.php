<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Throwable;

class UnexpectedExcelCellClassException extends ExcelImportConfigurationException
{
    public function __construct(string $givenClass, Throwable $previous = null)
    {
        parent::__construct(sprintf('Class %s does not extend %s.', $givenClass, AbstractExcelCell::class), 0, $previous);
    }
}