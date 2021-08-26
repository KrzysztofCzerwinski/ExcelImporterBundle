<?php

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\MessageInterface;

/**
 * An EXCEL cell that requires value to be a valid number
 */
class FloatExcelCell extends AbstractExcelCell
{

    /**
     * @inheritDoc
     */
    protected function getParsedValue(): ?float
    {
        return null !== $this->rawValue ? (float)$this->rawValue : null;
    }

    /**
     * @inheritDoc
     */
    protected function validateValueRequirements(): ?string
    {
        if (!is_numeric($this->rawValue)) {

            return $this->createErrorMessageWithNamePrefix(MessageInterface::NUMERIC_VALUE_REQUIRED);
        }

        return null;
    }
}