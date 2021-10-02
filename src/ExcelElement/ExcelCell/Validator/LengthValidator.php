<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator;

use function strlen;

class LengthValidator extends AbstractValidator
{
    /** @var int */
    private $minLength;

    /** @var int */
    private $maxLength;


    public function __construct(string $message, int $maxLength, int $minLength = 0)
    {
        parent::__construct($message);

        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
    }


    public static function getDefaultMessage(): string
    {
        return 'excel_importer.validator.messages.length_validator_default_message';
    }


    public function isExcelCellValueValid(string $rawValue): bool
    {
        $valueLength = strlen($rawValue);

        return $valueLength >= $this->minLength && $valueLength <= $this->maxLength;
    }

    protected function getReplaceablePropertiesAsParams(): array
    {
        return ['%minLength%' => $this->minLength, '%maxLength%' => $this->maxLength];
    }
}