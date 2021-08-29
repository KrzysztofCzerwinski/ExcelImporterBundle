<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Throwable;

class NotExistingModelClassException extends AnnotationConfigurationException
{
    public function __construct(string $givenClass, Throwable $previous = null)
    {
        parent::__construct(sprintf("given class model '%s' does not exist", $givenClass), 0, $previous);
    }
}