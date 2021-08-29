<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator;

use Kczer\ExcelImporterBundle\Exception\MissingValidatorOptionsException;
use function array_keys;

abstract class AbstractValidator
{
    /** @var array */
    protected $options;

    /** @var string */
    private $message;

    /**
     * @param array{errorMessage: string} $options Options custom for reach validator (error message is common for each) <br>
     *  Each option can be wrapped into curly braces to display its value like '{someOption}'
     *
     * @throws MissingValidatorOptionsException
     */
    public function __construct(array $options)
    {
        $this->validateOptions($options, $this->getRequiredOptionNames());
        $this->message = $options['message'] ?? '';
        $this->options = $options;
    }

    public function getMessage(): string
    {
        $errorMessage = $this->message;
        foreach ($this->options as $optionName => $optionValue) {
            if ('message' !== $optionName)
                $errorMessage = str_replace("{{$optionName}}" , (string)$optionValue, $errorMessage);
        }

        return $errorMessage;
    }

    /**
     * @return bool True, if value is valid, false otherwise
     */
    public abstract function isExcelCellValueValid(string $rawValue): bool;

    /**
     * @return string[]
     */
    protected abstract function getRequiredOptionNames(): array;

    /**
     * @throws MissingValidatorOptionsException
     */
    private function validateOptions(array $options, array $requiredOptions): void
    {
        $missingOptionNames = array_diff($requiredOptions, array_keys($options));
        if (!empty($missingOptionNames)) {

            throw new MissingValidatorOptionsException($missingOptionNames);
        }
    }
}