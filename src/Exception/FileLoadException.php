<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Exception;
use Throwable;

class FileLoadException extends Exception
{
    public function __construct(string $excelFileName, Throwable $previous = null)
    {
        parent::__construct(sprintf('Failed loading file %s', $excelFileName), 0, $previous);
    }
}