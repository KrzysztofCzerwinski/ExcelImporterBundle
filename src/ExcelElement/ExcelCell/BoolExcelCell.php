<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use Symfony\Contracts\Translation\TranslatorInterface;
use function array_merge;
use function in_array;
use function strtolower;

/**
 * An EXCEL cell that treats strings defined in SUPPORTED_TRUE_VALUES as true, an others as false
 */
class BoolExcelCell extends AbstractExcelCell
{
    public function __construct(
        TranslatorInterface $translator,
        private array       $trueValues,
        private array       $falseValues,
        private bool        $emptyAsFalse
    )
    {
        parent::__construct($translator);
        $this->validateObligatory = !$emptyAsFalse;
    }


    /**
     * @inheritDoc
     */
    protected function getParsedValue(): ?bool
    {
        $rawValueLowercase = $this->getRawValueLowercase();
        if (in_array($rawValueLowercase, $this->trueValues)) {

            return true;
        } elseif ($this->emptyAsFalse || in_array($rawValueLowercase, $this->falseValues)) {

            return false;
        }

        return null;
    }

    public function getDisplayValue(): string
    {
        $value = $this->getParsedValue();
        if (null !== $value) {

            return $this->translator->trans($value ?
                'excel_importer.excel_cell.bool.true_display_value' :
                'excel_importer.excel_cell.bool.false_display_value');
        }

        return parent::getDisplayValue();
    }

    /**
     * @inheritDoc
     */
    protected function validateValueRequirements(): ?string
    {
        if (
            !$this->emptyAsFalse &&
            null !== $this->rawValue &&
            !in_array($this->getRawValueLowercase(), array_merge($this->trueValues, $this->falseValues))
        ) {

            return $this->createErrorMessageWithNamePrefix('excel_importer.validator.messages.bool_value_required');
        }

        return null;
    }

    private function getRawValueLowercase(): string
    {
        return strtolower((string)$this->rawValue);
    }
}