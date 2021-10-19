<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\TemporaryFileManager;

use Exception;
use Throwable;
use function error_get_last;

class TemporaryFileCreationException extends Exception
{
    public function __construct(string $filePath, Throwable $previous = null)
    {

        parent::__construct(sprintf(
            'An exception occurred during creating file %s: %s',
            $filePath,
            error_get_last()
        ), 0, $previous);
    }
}