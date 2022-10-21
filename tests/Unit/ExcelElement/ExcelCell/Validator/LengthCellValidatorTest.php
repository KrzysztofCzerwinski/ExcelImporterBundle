<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Unit\ExcelElement\ExcelCell\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\LengthCellValidator;

class LengthCellValidatorTest extends AbstractCellValidatorTest
{
    protected function getValidator(): AbstractCellValidator
    {
        return new LengthCellValidator('', 12);
    }

    /**
     * @inheritDoc
     */
    protected function getValidValues(): array
    {
        return [
            'string',
            'valid string',
        ];
    }

    /**
     * @inheritDoc
     *
     * @noinspection SpellCheckingInspection
     */
    protected function getInvalidValues(): array
    {
        return [
            'nvalid string',
            'invalid string',
        ];
    }
}
