<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\MessageInterface;
use function key_exists;

/**
 * EXCEL cell type that requires value to be in a certain range (specified by getDictionary method)
 */
abstract class AbstractDictionaryExcelCell extends AbstractExcelCell
{
    /** @var ?array */
    private $dictionary = null;


    /**
     * @return array Array with string keys, which will be compared against excel values, and which values will be returned on getValue() call
     */
    protected abstract function getDictionary(): array;


    /**
     * @inheritDoc
     */
    protected function getParsedValue()
    {
        $this->initializeDictionaryIfNotReady();

        return null !== $this->rawValue ? $this->dictionary[$this->rawValue] : null;
    }

    /**
     * @inheritDoc
     */
    protected function validateValueRequirements(): ?string
    {
        $this->initializeDictionaryIfNotReady();

        if (!key_exists($this->rawValue, $this->dictionary)) {

            return $this->createErrorMessageWithNamePrefix(MessageInterface::DICTIONARY_VALUE_NOT_FOUND);
        }

        return null;
    }

    private function initializeDictionaryIfNotReady(): void
    {
        $this->dictionary = $this->dictionary ?? $this->getDictionary();
    }
}