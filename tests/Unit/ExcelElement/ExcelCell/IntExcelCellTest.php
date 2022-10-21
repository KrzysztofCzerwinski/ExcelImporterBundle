<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Unit\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\IntegerExcelCell;

class IntExcelCellTest extends AbstractExcelCellTest
{
    protected function getExcelCell(): IntegerExcelCell
    {
        return
            (new IntegerExcelCell($this->createTranslatorMock()))
                ->setRequired(true)
                ->setRawValue('1')
        ;
    }

    protected function getExpectedType(): string
    {
        return 'int';
    }

    protected function getExpectedValue(): int
    {
        return 1;
    }
}
