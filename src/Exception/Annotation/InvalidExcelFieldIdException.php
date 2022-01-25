<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Throwable;

class InvalidExcelFieldIdException extends AnnotationConfigurationException
{
    public function __construct(string $fieldId, Throwable $previous = null)
    {
        parent::__construct(
            "'$fieldId' is not valid field id. excel field id should contain from at least one letter column key and number row number",
            0,
            $previous
        );
    }
}