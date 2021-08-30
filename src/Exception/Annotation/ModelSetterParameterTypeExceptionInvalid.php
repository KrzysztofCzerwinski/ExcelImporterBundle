<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Throwable;
use function sprintf;

class InvalidDisplayModelSetterParameterTypeException extends AnnotationConfigurationException
{
    public function __construct(string $displayModelClass, string $setterName, string $parameterName, ?string $specifiedParameterType, Throwable $previous = null)
    {
        parent::__construct(sprintf("Parameter %s of Setter %s::%s must be of type string but is declared as %s", $parameterName, $displayModelClass, $setterName, $specifiedParameterType), 0, $previous);
    }
}