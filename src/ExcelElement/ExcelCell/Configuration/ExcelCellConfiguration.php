<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedValidatorClassException;
use function get_class;
use function is_a;

class ExcelCellConfiguration
{
    /** @var string */
    private $excelCellClass;

    /** @var string */
    private $cellName;

    /** @var bool */
    private $cellRequired;

    /** @var bool */
    private $column;

    /** @var AbstractCellValidator[] */
    private $validators;

    /**
     * @param string $excelCellClass Excel cell class extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell
     * @param string $cellName Cell name in EXCEL file
     * @param bool $cellRequired Whether cell value is required in an EXCEL file
     * @param bool $isColumn Whether cell value is taken from column in current row. static field otherwise
     * @param AbstractCellValidator[] Validator classes extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\ValidatorInterface that will validate raw value
     *
     * @throws UnexpectedClassException
     */
    public function __construct(string $excelCellClass, string $cellName, bool $cellRequired = true, bool $isColumn = true, array $validators = [])
    {
        if (!is_a($excelCellClass, AbstractExcelCell::class, true)) {

            throw new UnexpectedExcelCellClassException($excelCellClass);
        }
        $this->validateValidatorClasses($validators);
        $this->excelCellClass = $excelCellClass;
        $this->cellName = $cellName;
        $this->cellRequired = $cellRequired;
        $this->column = $isColumn;
        $this->validators = $validators;
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
     * @param object[] $validators
     *
     * @throws UnexpectedValidatorClassException
     */
    private function validateValidatorClasses(array $validators): void
    {
        foreach ($validators as $validator) {
            if (!($validator instanceof AbstractCellValidator)) {

                throw new UnexpectedValidatorClassException(get_class($validator));
            }
        }
    }

}