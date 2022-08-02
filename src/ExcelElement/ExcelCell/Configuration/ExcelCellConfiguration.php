<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedValidatorClassException;
use function is_a;

class ExcelCellConfiguration
{
    /**
     * @param string $excelCellClass Excel cell class extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell
     * @param string $cellName Cell name in EXCEL file
     * @param bool $cellRequired Whether cell value is required in an EXCEL file
     * @param bool $column Whether cell value is taken from column in current row. static field otherwise
     * @param AbstractCellValidator[] $validators Validator classes extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\ValidatorInterface that will validate raw value
     *
     * @throws UnexpectedClassException
     */
    public function __construct(
        private string $excelCellClass,
        private string $cellName,
        private bool   $cellRequired = true,
        private bool   $column = true,
        private array  $validators = [],
    ) {
        if (!is_a($excelCellClass, AbstractExcelCell::class, true)) {

            throw new UnexpectedExcelCellClassException($excelCellClass);
        }
        $this->validateValidatorClasses();
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

    public function isColumn(): bool
    {
        return $this->column;
    }

    /**
     * @return AbstractCellValidator[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * @param AbstractCellValidator[] $validators
     */
    public function setValidators(array $validators): self
    {
        $this->validators = $validators;
        return $this;
    }


    /**
     * @throws UnexpectedValidatorClassException
     */
    private function validateValidatorClasses(): void
    {
        foreach ($this->validators as $validator) {
            if (!($validator instanceof AbstractCellValidator)) {

                throw new UnexpectedValidatorClassException($validator::class);
            }
        }
    }

}
