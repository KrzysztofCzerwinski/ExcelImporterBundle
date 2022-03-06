<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\LengthCellValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Length extends AbstractExcelValidator
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
        parent::__construct($annotationData + ['message' => LengthCellValidator::getDefaultMessage()]);

        $this->minLength = $annotationData['minLength'] ?? 0;
        $this->maxLength = $annotationData['maxLength'];
    }

    /**
     * @return LengthCellValidator
     */
    public function getRelatedValidator(): AbstractValidator
    {
        return new LengthCellValidator($this->message, $this->maxLength, $this->minLength);
    }
}