<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidAnnotationParamException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedAnnotationOptionException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionExpectedDataTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionValueDataTypeException;
use function array_diff;
use function array_keys;
use function current;
use function is_array;
use function key;

/**
 * @Annotation
 */
abstract class AbstractOptionsAnnotation
{
    public const SUPPORTED_OPTION_EXPECTED_DATA_TYPES = [
        'string',
        'int',
        'float',
        'bool',
        'array',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $options;

    /**
     * @throws InvalidAnnotationParamException
     * @throws UnexpectedAnnotationOptionException
     * @throws UnexpectedOptionValueDataTypeException
     * @throws UnexpectedOptionExpectedDataTypeException
     */
    public function __construct(array $annotationData)
    {
        $options = $annotationData['options'] ?? [];
        if (!is_array($options)) {

            throw new InvalidAnnotationParamException('options', static::class, $options, 'array');
        }
        $this
            ->validateOptionsNames($options)
            ->validateOptionTypes($options);

        $this->options = $options;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return array<string, string>
     * Array with keys as options name
     * and values as types (basic PHP types only- supported types in AbstractOptionsAnnotation::SUPPORTED_OPTION_EXPECTED_DATA_TYPES)
     */
    protected abstract function getSupportedOptions(): array;

    /**
     * @throws UnexpectedAnnotationOptionException
     */
    private function validateOptionsNames(array $options): self
    {
        $supportedOptions = array_keys($this->getSupportedOptions());
        $unknownOptions = array_diff($options, $supportedOptions);
        if (empty($unknownOptions)) {

            return $this;
        }

        throw new UnexpectedAnnotationOptionException(current($unknownOptions), $supportedOptions, static::class);
    }

    /**
     * @throws UnexpectedOptionValueDataTypeException
     * @throws UnexpectedOptionExpectedDataTypeException
     */
    private function validateOptionTypes(array $options): void
    {
        $supportedOptions = $this->getSupportedOptions();
        /**
         * @var array<string, array{mixed, string}> $optionsTypeData
         */
        $optionsTypeData = array_merge_recursive($options, $supportedOptions);
        $unsupportedExpectedTypes = array_diff($supportedOptions, self::SUPPORTED_OPTION_EXPECTED_DATA_TYPES);
        if (!empty($unsupportedExpectedTypes)) {

            throw new UnexpectedOptionExpectedDataTypeException(
                key($unsupportedExpectedTypes),
                current($unsupportedExpectedTypes),
                self::SUPPORTED_OPTION_EXPECTED_DATA_TYPES,
                static::class
            );
        }
        foreach ($optionsTypeData as $optionName => [$optionValue, $expectedOptionValue]) {
            $typeCheckFunction = "is_$expectedOptionValue";
            if (!$typeCheckFunction($optionValue)) {

                throw new UnexpectedOptionValueDataTypeException($optionName, $expectedOptionValue, $optionValue, static::class);
            }
        }
    }
}