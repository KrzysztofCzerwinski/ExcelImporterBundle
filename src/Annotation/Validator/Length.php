<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\LengthValidator;
use Kczer\ExcelImporterBundle\MessageInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Length extends AbstractExcelColumnValidator
{
    /** @var int */
    private $minLength;

    /**
     * @Required
     *
     * @var int
     */
    private $maxLength;

    /**
     * @param array{minLength: int|null, maxLength: int, message: string} $annotationData
     */
    public function __construct(array $annotationData)
    {
        parent::__construct($annotationData + ['message' => MessageInterface::LENGTH_VALIDATOR_DEFAULT_MESSAGE]);

        $this->minLength = $annotationData['minLength'] ?? 0;
        $this->maxLength = $annotationData['maxLength'];
    }

    /**
     * @return LengthValidator
     */
    public function getRelatedValidator(): AbstractValidator
    {
        return new LengthValidator($this->maxLength, $this->minLength, $this->message);
    }
}