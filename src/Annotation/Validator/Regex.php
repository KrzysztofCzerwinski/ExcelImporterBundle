<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\RegexValidator;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidRegexExpressionException;
use Kczer\ExcelImporterBundle\MessageInterface;
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
        parent::__construct($annotationData + ['message' => MessageInterface::REGEX_VALIDATOR_DEFAULT_MESSAGE]);

        $this->pattern = $annotationData['pattern'];
    }

    /**
     * @return RegexValidator
     *
     * @throws InvalidRegexExpressionException
     */
    public function getRelatedValidator(): AbstractValidator
    {
        return new RegexValidator($this->pattern, $this->message);
    }
}