<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Unit\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\FloatExcelCell;

class FloatExcelCellTest extends AbstractExcelCellTest
{
    protected function getExcelCell(): FloatExcelCell
    {
        return
            (new FloatExcelCell($this->createTranslatorMock()))
                ->setRequired(true)
                ->setRawValue('1.123')
        ;
    }

    protected function getExpectedType(): string
    {
        return 'float';
    }

    protected function getExpectedValue(): float
    {
        return 1.123;
    }
}
