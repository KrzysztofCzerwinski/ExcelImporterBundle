<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Exception;
use Throwable;

class InvalidNamedColumnKeyException extends Exception
{
    public function __construct(string $columnKey, Throwable $previous = null)
    {
        parent::__construct("Column key '$columnKey' is not valid as it can be mistaken for field id", $previous);
    }
}