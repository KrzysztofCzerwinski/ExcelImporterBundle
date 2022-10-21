<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Unit\ExcelElement\ExcelCell\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\RegexCellValidator;

class RegexCellValidatorTest extends AbstractCellValidatorTest
{

    protected function getValidator(): AbstractCellValidator
    {
        return new RegexCellValidator('', '\d+ string \d+');
    }

    /**
     * @inheritDoc
     */
    protected function getValidValues(): array
    {
        return [
            '1 string 1',
            '123 string 123',
            '1234 string 1234',
            '0 string 1',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getInvalidValues(): array
    {
        return [
            'invalid string',
            '1 string 1 ',
            '1 string ',
            'string',
        ];
    }
}
