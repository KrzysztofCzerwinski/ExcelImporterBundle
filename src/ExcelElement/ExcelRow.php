<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use function array_filter;
use function array_map;
use function array_merge;
use function implode;
use function in_array;

class ExcelRow
{
    /** @var AbstractExcelCell[] */
    private $excelCells;

    /** @var string[] */
    private $rowRelatedErrorMessages = [];

    public function getExcelCells(): array
    {
        return $this->excelCells;
    }

    public function setExcelCells(array $excelCells): ExcelRow
    {
        $this->excelCells = $excelCells;
        return $this;
    }

    public function addErrorMessage(string $errorMessage): void
    {
        $this->rowRelatedErrorMessages[] = $errorMessage;
    }


    public function addErrorMessages(array $errorMessage): void
    {
        $this->rowRelatedErrorMessages = array_merge($errorMessage, $this->rowRelatedErrorMessages);
    }

    public function getExcelCellRawValue(string $columnKey): ?string
    {
        return $this->excelCells[$columnKey]->getRawValue();
    }

    /**
     * @return mixed|null
     */
    public function getExcelCellValue(string $columnKey)
    {
        return $this->excelCells[$columnKey]->getValue();
    }

    /**
     * @param bool $attachValues Whether to add value info to the messages
     *
     * @return string[]
     */
    public function getAllErrorMessages(bool $attachValues = false): array
    {
        return array_filter(
            array_merge(
                $this->rowRelatedErrorMessages,
                array_map(static function (AbstractExcelCell $excelCell) use ($attachValues): ?string {
                    $errorMessage = $excelCell->getErrorMessage();

                    return $attachValues && null !== $errorMessage ? "$errorMessage ({$excelCell->getDisplayValue()})" : $errorMessage;
                }, $this->excelCells)
            )
        );
    }

    public function hasErrors(): bool
    {
        return !empty($this->rowRelatedErrorMessages) ||
            in_array(
                true,
                array_map(static function (AbstractExcelCell $excelCell): bool {
                    return $excelCell->hasError();
                }, $this->excelCells)
            );
    }

    /**
     * @param bool $attachValues Whether to add values info to message
     * @param string $separator string used to separate the messages
     *
     * @return string messages from all ExcelCells merged into one string
     */
    public function getMergedAllErrorMessages(bool $attachValues = false, string $separator = ' | '): string
    {
        return implode($separator, $this->getAllErrorMessages($attachValues));
    }
}