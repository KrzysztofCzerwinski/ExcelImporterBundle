<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Throwable;
use function gettype;
use function is_object;
use function sprintf;

class InvalidAnnotationParamException extends AnnotationConfigurationException
{
    public function __construct(string $paramName, string $annotationClass, $givenParam, string $expectedType, Throwable $previous = null)
    {
        parent::__construct(sprintf(
            "param '%s' from %s annotation is expected to be of %s type, %s given",
            $paramName,
            $annotationClass,
            $expectedType,
            is_object($givenParam) ? get_class($givenParam) : gettype($givenParam)
        ), 0, $previous);
    }
}