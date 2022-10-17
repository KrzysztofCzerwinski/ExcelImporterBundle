<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Unit\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\BoolExcelCell;

class BoolExcelCellTest extends AbstractExcelCellTest
{
    protected function getExcelCell(): BoolExcelCell
    {
        return
            (new BoolExcelCell($this->createTranslatorMock(), ['t'], ['f'], true))
                ->setRequired(true)
                ->setRawValue('t')
        ;
    }

    protected function getExpectedType(): string
    {
        return 'bool';
    }

    protected function getExpectedValue(): bool
    {
        return true;
    }
}
