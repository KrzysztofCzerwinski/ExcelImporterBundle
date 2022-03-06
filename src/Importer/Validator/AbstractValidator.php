<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer\Validator;

abstract class AbstractValidator
{
    /** @var string */
    private $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return array{string, array} Array with error message and params passed from validator
     */
    public function getMessageWithParams(): array
    {
        return [$this->message, $this->getReplaceablePropertiesAsParams()];
    }

    public abstract static function getDefaultMessage(): string;

    /**
     * @return array<string, string> Array of properties that will be passed as params array to the translations in format %propertyName% => propertyValue
     */
    protected abstract function getReplaceablePropertiesAsParams(): array;
}