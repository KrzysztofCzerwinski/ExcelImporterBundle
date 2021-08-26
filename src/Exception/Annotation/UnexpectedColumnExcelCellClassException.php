<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Throwable;

class UnexpectedColumnExcelCellClassException extends ModelExcelColumnConfigurationException
{
    public function __construct(string $givenClass, string $propertyName, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                "Target ExcelCell class '%s' attached to property '%s' does not exists or does not extend %s",
                $givenClass,
                $propertyName,
                AbstractExcelCell::class
            ),
            0,
            $previous
        );
    }
}