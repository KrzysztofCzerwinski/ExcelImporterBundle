<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use Symfony\Contracts\Translation\TranslatorInterface;
use function current;

class BoolReverseExcelCell extends ReverseExcelCell
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var string[] */
    private $trueValues;

    /** @var string[] */
    private $falseValues;

    /**
     * @param TranslatorInterface $translator
     * @param string[] $trueValues
     * @param string[] $falseValues
     */
    public function __construct(
        TranslatorInterface $translator,
        array $trueValues,
        array $falseValues
    )
    {
        $this->translator = $translator;
        $this->trueValues = $trueValues;
        $this->falseValues = $falseValues;
    }

    protected function getReversedExcelCellValue(): string
    {
        return current($this->getValue() ? $this->trueValues : $this->falseValues);
    }
}