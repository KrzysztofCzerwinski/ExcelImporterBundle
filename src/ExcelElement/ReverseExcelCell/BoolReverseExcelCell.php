<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use Symfony\Contracts\Translation\TranslatorInterface;
use function current;

class BoolReverseExcelCell extends ReverseExcelCell
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var bool */
   private $emptyAsFalse;

    public function __construct(
        TranslatorInterface $translator,
        bool $emptyAsFalse
    )
    {
        $this->translator = $translator;
        $this->emptyAsFalse = $emptyAsFalse;
    }

    /**
     * @param bool|null $value
     *
     * @return string
     */
    public function getReversedExcelCellValue($value): string
    {
        if ($this->emptyAsFalse) {

            return $this->translator->trans(true === $value ?
                'excel_importer.excel_cell.bool.true_display_value' :
                'excel_importer.excel_cell.bool.false_display_value'
            );
        }

        return null !== $value ? $this->translator->trans($value ?
            'excel_importer.excel_cell.bool.true_display_value' :
            'excel_importer.excel_cell.bool.false_display_value'
        ) : '';
    }
}