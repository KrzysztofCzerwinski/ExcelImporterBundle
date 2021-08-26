<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Exception;
use Throwable;

class JsonExcelRowsLoadException extends Exception
{
    public function __construct(string $json,Throwable $previous = null)
    {
        parent::__construct(sprintf('Parsing JSON resulted in error: %s. JSON: %s', json_last_error(), $json), 0, $previous);
    }
}