<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
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

    /** @var AbstractValidator[] */
    private $validators;

    /**
     * @param string $excelCellClass Excel cell class extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell
     * @param string $cellName Cell name in EXCEL file
     * @param bool $cellRequired Whether cell value is required in an EXCEL file
     * @param AbstractValidator[] Validator classes extending Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\ValidatorInterface that will validate raw value
     *
     * @throws UnexpectedClassException
     */
    public function __construct(string $excelCellClass, string $cellName, bool $cellRequired = true, array $validators = [])
    {
        if (!is_a($excelCellClass, AbstractExcelCell::class, true)) {

            throw new UnexpectedExcelCellClassException($excelCellClass);
        }
        $this->validateValidatorClasses($validators);
        $this->excelCellClass = $excelCellClass;
        $this->cellName = $cellName;
        $this->cellRequired = $cellRequired;
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

    /**
     * @return AbstractValidator[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * @param AbstractValidator[] $validators
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
            if (!($validator instanceof AbstractValidator)) {

                throw new UnexpectedValidatorClassException(get_class($validator));
            }
        }
    }

}