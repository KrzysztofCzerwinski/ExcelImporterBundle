<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

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

    /** @var array */
    protected $options;

    /**
     @param array{message: string} $annotationData
     */
    public function __construct(array $annotationData)
    {
        $this->options = $annotationData;
        $this->message = $annotationData['message'];
    }

    /**
     * @return string name of Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator extending class
     */
    public abstract function getValidatorClass(): string;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

}