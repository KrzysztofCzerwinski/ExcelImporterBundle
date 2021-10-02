<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
abstract class AbstractExcelColumnValidator
{
    /**
     * @Required
     *
     * @var string
     */
    protected $message;

    /**
     * @param array{message: string} $annotationData
     */
    public function __construct(array $annotationData)
    {
        $this->message = $annotationData['message'];
    }

    /**
     * @return AbstractValidator Instance of Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator extending class
     */
    public abstract function getRelatedValidator(): AbstractValidator;

    public function getMessage(): string
    {
        return $this->message;
    }
}