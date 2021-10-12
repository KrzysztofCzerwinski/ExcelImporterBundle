<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

abstract class AbstractReverseExcelCell
{
    /** @var mixed */
    private $value;

    /** @var string */
    private $rawValue;

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param mixed $value
     */
    public function setValue($value): self
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string
     */
    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    /**
     * @param string $rawValue
     * @return AbstractReverseExcelCell
     */
    public function setRawValue(string $rawValue): AbstractReverseExcelCell
    {
        $this->rawValue = $rawValue;
        return $this;
    }


    public function getReversedExcelCellValue(): string
    {
        return (string)$this->getValue();
    }

    private function reverseExcelCell(): void
    {
        $this->rawValue = $this->getReversedExcelCellValue();
    }
}