<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Throwable;

class InvalidRegexExpressionException extends AnnotationConfigurationException
{
    public function __construct(string $pattern, Throwable $previous = null)
    {
        parent::__construct(sprintf("'Pattern '%s' is not a valid regex expression", $pattern), 0, $previous);
    }
}