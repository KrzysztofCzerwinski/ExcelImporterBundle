<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use function is_a;

class ExcelCellConfiguration
{
    /** @var string */
    private $excelCellClass;

    /** @var string */
    private $cellName;

    /** @var bool */
    private $cellRequired;

    /**
     * @param string $excelCellClass Excel cell class extending KCzer\ExcelImporter\ExcelElement\ExcelCell\AbstractExcelCell
     * @param string $cellName Cell name in EXCEL file
     * @param bool $cellRequired Whether cell value is required in an EXCEL file
     *
     * @throws UnexpectedExcelCellClassException
     */
    public function __construct(string $excelCellClass, string $cellName, bool $cellRequired = true)
    {
        if (!is_a($excelCellClass, AbstractExcelCell::class, true)) {

            throw new UnexpectedExcelCellClassException($excelCellClass);
        }
        $this->excelCellClass = $excelCellClass;
        $this->cellName = $cellName;
        $this->cellRequired = $cellRequired;
    }

    public function getExcelCellClass(): string
    {
        return $this->excelCellClass;
    }

    /**
     * @return string
     */
    public function getCellName(): string
    {
        return $this->cellName;
    }


    public function isCellRequired(): bool
    {
        return $this->cellRequired;
    }

}