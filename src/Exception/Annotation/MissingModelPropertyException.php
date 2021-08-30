<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Throwable;

class MissingModelPropertyException extends AnnotationConfigurationException
{
    public function __construct(string $displayModelClass, string $propertyName, Throwable $previous = null)
    {
        parent::__construct(sprintf('Model %s must have settable property %s', $displayModelClass, $propertyName), 0, $previous);
    }
}