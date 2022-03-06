<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\RegexCellValidator;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidRegexExpressionException;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Regex extends AbstractExcelValidator
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
        parent::__construct($annotationData + ['message' => RegexCellValidator::getDefaultMessage()]);

        $this->pattern = $annotationData['pattern'];
    }

    /**
     * @return RegexCellValidator
     *
     * @throws InvalidRegexExpressionException
     */
    public function getRelatedValidator(): AbstractValidator
    {
        return new RegexCellValidator($this->message, $this->pattern);
    }
}