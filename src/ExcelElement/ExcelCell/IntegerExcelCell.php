<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use function ctype_digit;

/**
 * Integer EXCEL cell that requires value to be a valid int
 */
class IntegerExcelCell extends AbstractExcelCell
{
    /**
     * @inheritDoc
     */
    protected function getParsedValue(): ?int
    {
        return null !== $this->rawValue ? (int)$this->rawValue : null;
    }

    /**
     * @inheritDoc
     */
    protected function validateValueRequirements(): ?string
    {
        if (!ctype_digit($this->rawValue)) {

            return $this->createErrorMessageWithNamePrefix('excel_importer.validator.messages.int_value_required');
        }

        return null;
    }
}