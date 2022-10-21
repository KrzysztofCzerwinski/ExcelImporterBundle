<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Unit\ExcelElement\ExcelCell\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use PHPUnit\Framework\TestCase;
use function array_map;

abstract class AbstractCellValidatorTest extends TestCase
{
    abstract protected function getValidator(): AbstractCellValidator;

    /**
     * @return string[]
     */
    abstract protected function getValidValues(): array;

    /**
     * @return string[]
     */
    abstract protected function getInvalidValues(): array;

    public function testValidatesValid(): void
    {
        $this->assertNotContains(
            false,
            array_map([$this->getValidator(), 'isExcelCellValueValid'], $this->getValidValues())
        );
    }

    public function testValidatesInvalid(): void
    {
        $this->assertNotContains(
            true,
            array_map([$this->getValidator(), 'isExcelCellValueValid'], $this->getInvalidValues())
        );
    }
}
