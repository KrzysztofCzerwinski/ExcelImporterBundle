<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Exception;
use Throwable;

class ExcelFileLoadException extends Exception
{
    public function __construct(string $excelFileName, Throwable $previous = null)
    {
        parent::__construct(sprintf('Failed loading excel file %s', $excelFileName), 0, $previous);
    }
}