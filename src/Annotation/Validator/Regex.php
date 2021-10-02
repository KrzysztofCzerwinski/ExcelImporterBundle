<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\RegexValidator;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidRegexExpressionException;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Regex extends AbstractExcelColumnValidator
{
    /**
     * @Required
     *
     * @var string
     */
    private $pattern;

    /**
     * @param array{pattern: string, message: string} $annotationData
     */
    public function __construct(array $annotationData)
    {
        parent::__construct($annotationData + ['message' => RegexValidator::getDefaultMessage()]);

        $this->pattern = $annotationData['pattern'];
    }

    /**
     * @return RegexValidator
     *
     * @throws InvalidRegexExpressionException
     */
    public function getRelatedValidator(): AbstractValidator
    {
        return new RegexValidator($this->message, $this->pattern);
    }
}