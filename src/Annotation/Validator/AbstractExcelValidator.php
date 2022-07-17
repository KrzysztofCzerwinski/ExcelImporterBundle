<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;

/**
 * @Annotation
 * @Target({"PROPERTY", "CLASS"})
 */
#[Attribute(Attribute::TARGET_PROPERTY|Attribute::TARGET_CLASS)]
abstract class AbstractExcelValidator
{
    /**
     * @Required
     */
    protected string $message;

    /**
     * @param array{message: string} $data
     */
    public function __construct(array $data = [])
    {
        $this->message = $data['message'];
    }

    public abstract function getRelatedValidator(): AbstractValidator;

    public function getMessage(): string
    {
        return $this->message;
    }
}