<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator;

abstract class AbstractValidator
{
    /** @var string */
    private $message;


    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return array{string, array, ?string} Array with error message, params passed from validator and domain for translation
     */
    public function getMessageWithParams(): array
    {
        return [$this->message, $this->getReplaceablePropertiesAsParams()];
    }

    /**
     * @return string
     */
    public abstract static function getDefaultMessage(): string;

    /**
     * @return bool True, if value is valid, false otherwise
     */
    public abstract function isExcelCellValueValid(string $rawValue): bool;

    /**
     * @return array Array of properties that will be passed as params array to the translations in format %propertyName% => propertyValue
     */
    protected abstract function getReplaceablePropertiesAsParams(): array;
}