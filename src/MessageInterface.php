<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle;

interface MessageInterface
{
    /** @var string */
    public const VALUE_REQUIRED = 'Wartość wymagana';

    /** @var string */
    public const INT_VALUE_REQUIRED = 'Wymagana liczba całkowita';

    /** @var string */
    public const NUMERIC_VALUE_REQUIRED = 'Wymagana wartość liczbowa';

    /** @var string */
    public const DATETIME_STRING_VALUE_REQUIRED = 'Wymagana data';

    /** @var string */
    public const DICTIONARY_VALUE_NOT_FOUND = 'Wartość nie znaleziona w słowniku';
}