<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Throwable;
use function sprintf;

class DuplicateColumnKeyException extends ExcelImportConfigurationException
{
    public function __construct(string $columnKey, Throwable $previous = null)
    {
        parent::__construct(sprintf("Duplicated column key '%s'", $columnKey), 0, $previous);
    }
}