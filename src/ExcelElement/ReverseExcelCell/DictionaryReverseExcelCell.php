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

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function getReversedExcelCellValue($value): string
    {
        return null !== $value ? (string)array_search($value, $this->getDictionary()) : '';
    }
}