<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator;

use function str_replace;

abstract class AbstractValidator
{
    /** @var string */
    private $message;


    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return array{"0": string, "1": array} Array with error message and params passed from validator
     */
    public function getMessageWithParams(): array
    {
        return [$this->message, $this->getReplaceablePropertiesAsParams()];
    }

    /**
     * @return bool True, if value is valid, false otherwise
     */
    public abstract function isExcelCellValueValid(string $rawValue): bool;

    /**
     * @return array Array of properties that will be passed as params array to the translations in format %propertyName% => propertyValue
     */
    protected abstract function getReplaceablePropertiesAsParams(): array;
}