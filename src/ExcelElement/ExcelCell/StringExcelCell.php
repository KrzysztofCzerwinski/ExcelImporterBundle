<?php

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A Standard EXCEL cell type, that doesn't require any special validations
 */
class StringExcelCell extends AbstractExcelCell
{
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator);
    }


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