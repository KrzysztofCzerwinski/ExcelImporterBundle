<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;

class ModelPropertyMetadata
{
    /** @var string */
    public const SETTER_PREFIX = 'set';

    /** @var ExcelColumn */
    private $excelColumn;

    /** @var string */
    private $propertyName;


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

    public function hasColumnId(): bool
    {
        return null !== $this->excelColumn->getColumnKey();
    }
}