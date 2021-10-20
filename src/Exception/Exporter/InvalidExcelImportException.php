<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Exporter;

use Exception;
use Throwable;
use function sprintf;

class InvalidExcelImportException extends Exception
{
    public function __construct(string $modelClass, string $mergedAllErrorMessages, Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Can not merge models to existing EXCEL file as it does not match "%s" model class requirements. Errors found: %s ',
            $modelClass,
            $mergedAllErrorMessages
        ), 0, $previous);
    }
}