<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Throwable;
use function sprintf;

class DuplicateExcelIdentifierException extends ExcelImportConfigurationException
{
    public function __construct(string $excelIdentifier, Throwable $previous = null)
    {
        parent::__construct(sprintf("Duplicated column key '%s'", $excelIdentifier), 0, $previous);
    }
}