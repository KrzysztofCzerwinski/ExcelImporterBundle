<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Attribute;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\LengthCellValidator;
use Symfony\Contracts\Service\Attribute\Required;
use function is_int;
use const PHP_INT_MAX;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Length extends AbstractExcelColumnValidator
{
    private int $minLength;

    /**
     * @Required
     */
    private int $maxLength;

    /**
     * @param array{minLength: int|null, maxLength: int, message: string}|int $data
     */
    public function __construct(array|int $data = [], ?int $minLength = null, ?int $maxLength = null, ?string $message = null)
    {
        parent::__construct(['message' => $message ?? $data['message'] ?? LengthCellValidator::getDefaultMessage()]);

        $maxLength = is_int($data) ? $data : $maxLength;

        $this->minLength = $minLength ?? $data['minLength'] ?? 0;
        $this->maxLength = $maxLength ?? $data['maxLength'] ?? PHP_INT_MAX;
    }

    public function getRelatedValidator(): LengthCellValidator
    {
        return new LengthCellValidator($this->message, $this->maxLength, $this->minLength);
    }
}