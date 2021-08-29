<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\RegexAbstractValidator;
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
     * @throws InvalidRegexExpressionException
     */
    public function __construct(array $annotationData)
    {
        parent::__construct($annotationData + ['message' => MessageInterface::REGEX_VALIDATOR_DEFAULT_MESSAGE]);

        $pattern = $annotationData['pattern'];
        if (!RegexAbstractValidator::isRegexValid($pattern)) {

            throw new InvalidRegexExpressionException($pattern);
        }
    }

    public function getValidatorClass(): string
    {
       return RegexAbstractValidator::class;
    }
}