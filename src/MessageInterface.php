<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle;

interface MessageInterface
{
    public const VALUE_REQUIRED = 'Wartość wymagana';

    public const INT_VALUE_REQUIRED = 'Wymagana liczba całkowita';

    public const NUMERIC_VALUE_REQUIRED = 'Wymagana wartość liczbowa';

    public const DATETIME_STRING_VALUE_REQUIRED = 'Wymagana data';

    public const DICTIONARY_VALUE_NOT_FOUND = 'Wartość nie znaleziona w słowniku';

    public const REGEX_VALIDATOR_DEFAULT_MESSAGE = 'Wartość nie spełnia wyrażenia regularnego "{pattern}"';

    public const LENGTH_VALIDATOR_DEFAULT_MESSAGE = 'Wartość nie mieści się w przeziale długości od {minLength} do {maxLength}';
}