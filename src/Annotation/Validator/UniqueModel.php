<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\UniqueModelValidator;


/**
 * @Annotation
 * @Annotation\Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
class UniqueModel extends AbstractExcelImportValidator
{
    /** @var string[] */
    private array $fields;

    /**
     * @param array{fields: array<int, string>|string|null, value: array<int, string>|string|null}|string $data
     */
    public function __construct(array|string $data = [], ?array $fields = null, ?string $message = null)
    {
        parent::__construct(['message' => $message ?? $data['message'] ?? UniqueModelValidator::getDefaultMessage()]);
        if (is_string($data)) {
            $this->fields = [$data];

            return;
        }
        $this->fields =
                $fields ??
            (array)($data['fields'] ?? []) ?:
            (array)($data['value'] ?? []);
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