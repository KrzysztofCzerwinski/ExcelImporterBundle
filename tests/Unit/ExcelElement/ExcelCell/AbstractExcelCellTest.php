<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Unit\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;
use function ucfirst;

abstract class AbstractExcelCellTest extends TestCase
{
    abstract protected function getExcelCell(): AbstractExcelCell;

    abstract protected function getExpectedType(): string;

    abstract protected function getExpectedValue(): mixed;

    public function testParsesValue(): void
    {
        $excelCell = $this->getExcelCell();

        $value = $excelCell->getValue();

        $this->testType($value);

        $this->assertEquals($this->getExpectedValue(), $value);
    }

    protected function testType(mixed $value): void
    {
        $expectedTypeSuffix = ucfirst($this->getExpectedType());
        $this->{"assertIs$expectedTypeSuffix"}($value);
    }

    protected function createTranslatorMock(): TranslatorInterface
    {
        $translator = $this->createMock(Translator::class);

        $translator
            ->expects($this->any())
            ->method('trans')
            ->willReturn('')
        ;

        return $translator;
    }


}
