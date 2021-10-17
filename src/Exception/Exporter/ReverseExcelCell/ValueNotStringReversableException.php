<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Exporter\ReverseExcelCell;

use Exception;
use Throwable;
use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class ValueNotStringReversableException extends Exception
{
    /**
     * @param mixed $value
     */
    public function __construct($value, string $baseExcelCellClass, Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'value of type %s from %s can not be parsed to string or does not implements __toString() method',
            is_object($value) ? get_class($value) : gettype($value),
            $baseExcelCellClass
        ), 0, $previous);
    }
}