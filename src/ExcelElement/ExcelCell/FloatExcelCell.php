<?php

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * An EXCEL cell that requires value to be a valid number
 */
class FloatExcelCell extends AbstractExcelCell
{
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator);
    }


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

            return $this->createErrorMessageWithNamePrefix('excel_importer.validator.messages.numeric_value_required');
        }

        return null;
    }
}