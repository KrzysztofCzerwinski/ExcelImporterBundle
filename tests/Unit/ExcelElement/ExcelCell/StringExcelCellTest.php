<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Unit\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\StringExcelCell;

class StringExcelCellTest extends AbstractExcelCellTest
{
    protected function getExcelCell(): StringExcelCell
    {
        return
            (new StringExcelCell($this->createTranslatorMock()))
                ->setRequired(true)
                ->setRawValue('string')
        ;
    }

    protected function getExpectedType(): string
    {
        return 'string';
    }

    protected function getExpectedValue(): string
    {
        return 'string';
    }
}
