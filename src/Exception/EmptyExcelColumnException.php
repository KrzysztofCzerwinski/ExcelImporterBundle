<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Exception;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Throwable;
use function get_class;

class EmptyExcelColumnException extends Exception
{
    /** @var AbstractExcelCell */
    private $excelCell;

    /** @var string */
    private $columnKey;

    public function __construct(AbstractExcelCell $excelCell, string $columnKey, Throwable $previous = null)
    {
        $this->excelCell = $excelCell;
        $this->columnKey = $columnKey;

        parent::__construct(
            sprintf("Empty column '%s' of key '%s', expected %s compatible values", $excelCell->getName(), (string)$columnKey, get_class($excelCell)),
            0,
            $previous
        );
    }

    public function getCellName(): AbstractExcelCell
    {
        return $this->excelCell;
    }

    /**
     * @return string
     */
    public function getColumnKey()
    {
        return $this->columnKey;
    }


}