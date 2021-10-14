<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use function array_search;

class DictionaryReverseExcelCell extends ReverseExcelCell
{
    /** @var array */
    private $dictionary;

    public function getDictionary(): array
    {
        return $this->dictionary;
    }

    public function setDictionary(array $dictionary): self
    {
        $this->dictionary = $dictionary;
        return $this;
    }


    protected function getReversedExcelCellValue(): string
    {
        /** @var array $value */
        $value = $this->getValue();

        return (string)array_search($value, $this->getDictionary());
    }
}