<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use Kczer\ExcelImporterBundle\Model\ModelMetadata;

class ReverseExcelCellManager
{
    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var array<string, ReverseExcelCell> Keys are property names */
    private $propertyReverseExcelCells;

    public function getModelMetadata(): ModelMetadata
    {
        return $this->modelMetadata;
    }

    public function setModelMetadata(ModelMetadata $modelMetadata): ReverseExcelCellManager
    {
        $this->modelMetadata = $modelMetadata;
        return $this;
    }

    public function getPropertyReverseExcelCells(): array
    {
        return $this->propertyReverseExcelCells;
    }

    public function setPropertyReverseExcelCells(array $propertyReverseExcelCells): ReverseExcelCellManager
    {
        $this->propertyReverseExcelCells = $propertyReverseExcelCells;
        return $this;
    }

    /**
     * @param object $model
     *
     * @return string[]
     */
    private function reverseModelToArray($model): array
    {
        $rawModelData = [];
        foreach ($this->getModelMetadata()->getModelPropertiesMetadata() as $propertyMetadata) {
            $rawModelData[$propertyMetadata->getExcelColumn()->getColumnKey()] = $this->getPropertyReverseExcelCells()[$propertyMetadata->getPropertyName()]->getRawValue();
        }

        return $rawModelData;
    }
}