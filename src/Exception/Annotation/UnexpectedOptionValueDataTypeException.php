<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Exception;
use Throwable;
use function get_class;
use function gettype;
use function is_object;

class UnexpectedOptionValueDataTypeException extends Exception
{
    /**
     * @param string $optionName
     * @param array $expectedOptionType
     * @param mixed $givenValue
     * @param string $annotationClass
     * @param Throwable|null $previous
     */
    public function __construct(string $optionName, array $expectedOptionType, $givenValue, string $annotationClass, Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Unexpected data type of option "%s" in annotation class "%s". Expected "%s", got "%s"',
            $optionName,
            $annotationClass,
            $expectedOptionType,
            is_object($givenValue) ? get_class($givenValue) : gettype($givenValue)
        ), 0, $previous);
    }
}