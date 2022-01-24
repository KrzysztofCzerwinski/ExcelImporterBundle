<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Throwable;

class InvalidExcelFieldIdException extends AnnotationConfigurationException
{
    public function __construct(string $fieldId, Throwable $previous = null)
    {
        parent::__construct(
            "'$fieldId' is not valid field id. excel field id in format [A-Z]+[0-9]+ required",
            0,
            $previous
        );
    }
}