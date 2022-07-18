<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Attribute;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\RegexCellValidator;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidRegexExpressionException;
use Symfony\Contracts\Service\Attribute\Required;
use function is_string;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Regex extends AbstractExcelColumnValidator
{
    /**
     * @Required
     */
    private string $pattern;

    /**
     * @param array{pattern: string, message: string}|string $data
     */
    public function __construct(array|string $data = [], ?string $pattern = null, ?string $message = null)
    {
        parent::__construct(['message' => $message ?? $data['message'] ?? RegexCellValidator::getDefaultMessage()]);

        $pattern = is_string($data) ? $data : $pattern;

        $this->pattern = $pattern ?? $data['pattern'] ?? '';
    }

    /**
     * @throws InvalidRegexExpressionException
     */
    public function getRelatedValidator(): RegexCellValidator
    {
        return new RegexCellValidator($this->message, $this->pattern);
    }
}