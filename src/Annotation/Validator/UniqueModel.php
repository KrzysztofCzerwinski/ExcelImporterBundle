<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Doctrine\Common\Annotations\Annotation;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\UniqueModelValidator;


/**
 * @Annotation
 * @Annotation\Target({"CLASS"})
 */
class UniqueModel extends AbstractExcelImportValidator
{
    /** @var string[] */
    private $fields;

    /**
     * @param array{fields: array<int, string>|string|null, value: array<int, string>|string|null} $annotationData
     */
    public function __construct(array $annotationData)
    {
        parent::__construct($annotationData + ['message' => UniqueModelValidator::getDefaultMessage()]);

        $this->fields = (array)($annotationData['fields'] ?? null) ?: (array)$annotationData['value'];
    }

    /**
     * @return string[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @return UniqueModelValidator
     */
    public function getRelatedValidator(): AbstractValidator
    {
        return new UniqueModelValidator($this->message, $this->fields);
    }
}