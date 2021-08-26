<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\DictionaryExcelCell;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractDictionaryExcelCell;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Throwable;

class UnexpectedExcelDictionaryClassException extends ExcelImportConfigurationException
{
    public function __construct(string $givenClass, Throwable $previous = null)
    {
        parent::__construct(sprintf('Class %s does not exists or does not extend %s', $givenClass, AbstractDictionaryExcelCell::class), 0, $previous);
    }
}