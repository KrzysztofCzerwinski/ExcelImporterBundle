<?php

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

/**
 * A Standard EXCEL cell type, that doesn't require any special validations
 */
class StringExcelCell extends AbstractExcelCell
{
    /**
     * @inheritDoc
     */
    protected function getParsedValue(): ?string
    {
        return null !== $this->rawValue ? (string)$this->rawValue : null;
    }

    /**
     * @inheritDoc
     */
    protected function validateValueRequirements(): ?string
    {
        return null;
    }
}