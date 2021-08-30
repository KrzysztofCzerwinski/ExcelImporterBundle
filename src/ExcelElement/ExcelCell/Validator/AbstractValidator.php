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

    public function getMessage(): string
    {
        $errorMessage = $this->message;
        foreach ($this->getReplaceableProperties() as $propertyName => $propertyValue) {
            $errorMessage = str_replace("{{$propertyName}}" , (string)$propertyValue, $errorMessage);
        }

        return $errorMessage;
    }

    /**
     * @return bool True, if value is valid, false otherwise
     */
    public abstract function isExcelCellValueValid(string $rawValue): bool;

    /**
     * @return array Array of properties that can be replaced with {propertyName} syntax in message
     */
    protected abstract function getReplaceableProperties(): array;
}