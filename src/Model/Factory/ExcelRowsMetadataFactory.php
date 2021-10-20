<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model\Factory;

use Kczer\ExcelImporterBundle\Model\ExcelRowsMetadata;
use function end;
use function key;

class ExcelRowsMetadataFactory
{
    /**
     * @param array $rawExcelRows 1-indexed array with raw EXCEL data
     * @param int|null $headerExcelRowIndex 1-indexed EXCEL header index if any found. NULL otherwise
     * @param string[] $columnKeyMappings Array with human-readable EXCEL column keys as keys and EXCEL A...Z column keys as values if mapping found. NULL otherwise
     * @param bool $skippedFirstRow Whether first row was omitted during ExcelCell creation
     *
     * @return ExcelRowsMetadata
     */
    public static function createFromExcelImporterData(array $rawExcelRows, ?int $headerExcelRowIndex, ?array $columnKeyMappings, bool $skippedFirstRow): ExcelRowsMetadata
    {
        $firstRawExcelRowsIndex = $headerExcelRowIndex ?? 0;
        if (!empty($rawExcelRows) && !$skippedFirstRow) {
            $firstRawExcelRowsIndex = key($rawExcelRows) - 1;
        } elseif (!empty($rawExcelRows)) {
            $firstRawExcelRowsIndex = key($rawExcelRows);
        }

        end($rawExcelRows);
        $lastRawExcelRowsIndex = key($rawExcelRows);
        $lastRawExcelRowsIndex = null !== $lastRawExcelRowsIndex ? $lastRawExcelRowsIndex - 1 : null;


        return (new ExcelRowsMetadata())
            ->setFirstDataRowIndex($firstRawExcelRowsIndex)
            ->setLastDataRowIndex($lastRawExcelRowsIndex ?? $firstRawExcelRowsIndex)
            ->setColumnKeyMappings($columnKeyMappings);
    }
}