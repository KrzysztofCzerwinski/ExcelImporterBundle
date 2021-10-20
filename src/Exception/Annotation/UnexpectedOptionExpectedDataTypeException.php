<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Exception;
use Throwable;
use function implode;
use function sprintf;

class UnexpectedOptionExpectedDataTypeException extends Exception
{
    /**
     * @param string $optionName
     * @param string $givenOptionExpectedValue
     * @param string[] $supportedExpectedDataTypes
     * @param string $annotationClass
     * @param Throwable|null $previous
     */
    public function __construct(
        string $optionName,
        string $givenOptionExpectedValue,
        array $supportedExpectedDataTypes,
        string $annotationClass,
        Throwable $previous = null
    )
    {
        parent::__construct(sprintf(
            'Unsupported expected data type of option "%s" in annotation class "%s". Got "%s", supported expected data types are %s',
            $optionName,
            $annotationClass,
            $givenOptionExpectedValue,
            implode(', ', $supportedExpectedDataTypes)
        ), 0, $previous);
    }
}