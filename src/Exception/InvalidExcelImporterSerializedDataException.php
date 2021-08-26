<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Exception;
use Throwable;

class InvalidExcelImporterSerializedDataException extends Exception
{
    public function __construct(string $excelImporterClass, Throwable $previous = null)
    {
        parent::__construct(sprintf('Serialized %s instance can not be deserialized due to invalid serialized string', $excelImporterClass), 0, $previous);
    }
}