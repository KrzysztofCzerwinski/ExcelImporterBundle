<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

class ReverseExcelCell
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
        $this->reverseExcelCell();

        return $this;
    }

    /**
     * @return string
     */
    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    protected function getReversedExcelCellValue(): string
    {
        return (string)$this->getValue();
    }

    private function reverseExcelCell(): void
    {
        $this->rawValue = null !== $this->getValue() ? $this->getReversedExcelCellValue() : '';
    }
}