<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator;

use Kczer\ExcelImporterBundle\MessageInterface;
use function strlen;

class LengthValidator extends AbstractValidator
{
    /** @var int */
    private $minLength;

    /** @var int */
    private $maxLength;


    public function __construct(int $maxLength, int $minLength = 0, $message = MessageInterface::LENGTH_VALIDATOR_DEFAULT_MESSAGE)
    {
        parent::__construct($message);

        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
    }

    public function isExcelCellValueValid(string $rawValue): bool
    {
        $valueLength = strlen($rawValue);

        return $valueLength >= $this->minLength && $valueLength <= $this->maxLength;
    }

    protected function getReplaceableProperties(): array
    {
        return ['minLength' => $this->minLength, 'maxLength' => $this->maxLength];
    }
}