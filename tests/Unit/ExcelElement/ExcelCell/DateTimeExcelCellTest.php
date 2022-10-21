<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Unit\ExcelElement\ExcelCell;

use DateTime;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\DateTimeExcelCell;

class DateTimeExcelCellTest extends AbstractExcelCellTest
{
    protected function getExcelCell(): DateTimeExcelCell
    {
        return
            (new DateTimeExcelCell($this->createTranslatorMock()))
                ->setRequired(true)
                ->setRawValue('01.01.2022')
        ;
    }

    protected function getExpectedType(): string
    {
        return 'object';
    }

    protected function getExpectedValue(): DateTime
    {
        return new DateTime('01.01.2022');
    }

    protected function testType(mixed $value): void
    {
        parent::testType($value);

        $this->assertTrue($value instanceof DateTime);
    }
}
