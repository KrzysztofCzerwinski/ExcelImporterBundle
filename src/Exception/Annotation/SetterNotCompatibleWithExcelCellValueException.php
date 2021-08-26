<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\Model\ModelPropertyMetadata;
use Throwable;

class SetterNotCompatibleWithExcelCellValueException extends ModelExcelColumnConfigurationException
{
    public function __construct(AbstractExcelCell $excelCell, ModelPropertyMetadata $modelPropertyMetadata, Throwable $previous = null)
    {
        parent::__construct(
            sprintf(
                "setter '%s::%s' is not compatible with '%s' EXCEL CELL value type. Make sure that no errors in import occurred and proper data type is returned by Excel cell class",
                $modelPropertyMetadata->getExcelColumn()->getTargetExcelCellClass(),
                $modelPropertyMetadata->getSetterName(),
                get_class($excelCell)
            ),
            0,
            $previous
        );
    }
}