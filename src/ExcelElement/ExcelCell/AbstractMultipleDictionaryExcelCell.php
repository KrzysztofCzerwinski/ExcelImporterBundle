<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\Exception\DictionaryExcelCell\UnexpectedExcelDictionaryClassException;
use function is_a;

/**
 * Special dictionary EXCEL cell type, that takes a number of dictionary EXCEL cells (extending KCzer\ExcelImporter\MessageInterface\AbstractDictionaryExcelCell)
 * and merges its dictionaries into one dictionary
 */
abstract class AbstractMultipleDictionaryExcelCell extends AbstractDictionaryExcelCell
{

    /**
     * @return string[] Array containing fully qualified class names of DictionaryExcelCells
     */
    public abstract function getSubDictionaryExcelCellClasses(): array;

    /**
     * @inheritDoc
     *
     * @throws UnexpectedExcelDictionaryClassException
     */
    protected function getDictionary(): array
    {
        $dictionary = [];
        foreach ($this->getSubDictionaryExcelCellClasses() as $dictionaryExcelCellClass) {
            if (!is_a($dictionaryExcelCellClass, AbstractDictionaryExcelCell::class, true)) {

                throw new UnexpectedExcelDictionaryClassException($dictionaryExcelCellClass);
            }
            /** @var AbstractDictionaryExcelCell $dictionaryExcelCell */
            $dictionaryExcelCell = (new $dictionaryExcelCellClass($this->name));

            $dictionary += $dictionaryExcelCell->getDictionary();
        }

        return $dictionary;
    }
}