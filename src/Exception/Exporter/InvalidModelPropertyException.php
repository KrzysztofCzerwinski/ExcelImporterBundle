<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Exporter;

use Exception;
use Throwable;

class InvalidModelPropertyException extends Exception
{
    public function __construct(string $propertyName, string $className, Throwable $previous = null)
    {
        parent::__construct(\sprintf(
            'Property "%s" of class "%s" does not exist or is not annotated with @ExcelColumn annotation',
            $propertyName,
            $className
        ), 0, $previous);
    }
}