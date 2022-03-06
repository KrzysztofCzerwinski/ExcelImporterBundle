<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Exporter;

use Exception;
use Throwable;
use function implode;
use function sprintf;

class InvalidModelPropertyException extends Exception
{
    /**
     * @param string|string[] $properties
     */
    public function __construct($properties, string $className, Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Following properties of class "%s" do not exist or are not annotated with @ExcelColumn annotation: %s',
            $className,
            implode(', ', (array)$properties)
        ), 0, $previous);
    }
}