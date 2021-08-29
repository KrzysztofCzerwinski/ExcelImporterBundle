<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;

class ModelPropertyMetadata
{
    /** @var string */
    public const SETTER_PREFIX = 'set';

    /** @var ExcelColumn */
    private $excelColumn;

    /** @var string */
    private $propertyName;

    /** @var AbstractValidator[] */
    private $validators;


    public function getExcelColumn(): ExcelColumn
    {
        return $this->excelColumn;
    }

    public function setExcelColumn(ExcelColumn $excelColumn): ModelPropertyMetadata
    {
        $this->excelColumn = $excelColumn;
        return $this;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function setPropertyName(string $propertyName): ModelPropertyMetadata
    {
        $this->propertyName = $propertyName;
        return $this;
    }

    public function getSetterName(): string
    {
        return sprintf('%s%s', 'set', ucfirst($this->propertyName));
    }

    /**
     * @return AbstractValidator[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    public function setValidators(array $validators): ModelPropertyMetadata
    {
        $this->validators = $validators;
        return $this;
    }
}