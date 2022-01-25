<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Util;

use function strtoupper;

class FieldIdResolver
{
    public function isColumnKeyFieldIdentifier(string $columnKey): bool
    {
        /** @example A10 | C3 | abc 123 */
        return 1 === preg_match('|^[A-Z]+\s*\d+$|i', trim($columnKey));
    }

    /**
     * @return array{int, string} row number and column key correspondingly
     */
    public function resolveRowNumberColumnKey(string $fieldId): array
    {
        /** @example A10 | C3 | abc 123 */
        preg_match('|^([A-Z]+)\s*(\d+)$|i', trim($fieldId), $matches);
        [, $columnKey, $rowNumber] = $matches;

        return [(int)$rowNumber, strtoupper($columnKey)];
    }
}