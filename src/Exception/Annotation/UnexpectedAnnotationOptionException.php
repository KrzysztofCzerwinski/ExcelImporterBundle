<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Exception;
use Throwable;

class UnexpectedAnnotationOptionException extends Exception
{
    /**
     * @param string $optionName
     * @param string $annotationClass
     * @param string[] $supportedOptions
     * @param Throwable|null $previous
     */
    public function __construct(string $optionName, array $supportedOptions, string $annotationClass, Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Unsupported option "%s" in "%s" annotation class. Supported options are %s',
            $optionName,
            $annotationClass,
            implode(', ', $supportedOptions)
        ), 0, $previous);
    }
}