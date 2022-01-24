<?php

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use function preg_match;
use function str_replace;

/**
 * An EXCEL cell that requires value to be a valid number or number with unit
 */
class FloatExcelCell extends AbstractExcelCell
{
    public function getDisplayValue(): string
    {
        return str_replace(['\\', '  '], ' ', $this->rawValue);
    }

    /**
     * @inheritDoc
     */
    protected function getParsedValue(): ?float
    {
        if (null === $this->rawValue) {

            return null;
        }
        /** @example 100 | 100,12\zł | 9.999 kg */
        preg_match('/^(\d+(?:[,.]\d+)?)(?:[\s\\\]*\p{L}+)?$/iu', $this->rawValue, $matches);
        [, $value] = $matches;

        return (float)(str_replace(',', '.', $value));
    }

    /**
     * @inheritDoc
     */
    protected function validateValueRequirements(): ?string
    {
        /** @example 100 | 100,12\zł | 9.999   kg */
        return 1 === preg_match('/^\d+(?:[,.]\d+)?(?:[\s\\\]*\p{L}+)?$/iu', $this->rawValue) ?
            null :
            $this->createErrorMessageWithNamePrefix('excel_importer.validator.messages.numeric_value_required');
    }
}