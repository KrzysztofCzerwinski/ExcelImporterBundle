<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\TemporaryFileManager;

use Exception;
use Throwable;

class FileAlreadyExistsException extends Exception
{
    public function __construct(string $filePath, Throwable $previous = null)
    {
        parent::__construct("File $filePath already exists and therefore can not be created", 0, $previous);
    }
}