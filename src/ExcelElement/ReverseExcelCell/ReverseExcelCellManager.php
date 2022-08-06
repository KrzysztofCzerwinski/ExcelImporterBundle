<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell;

use Kczer\ExcelImporterBundle\Exception\Exporter\NotGettablePropertyException;
use Kczer\ExcelImporterBundle\Exception\Exporter\ReverseExcelCell\ValueNotStringReversableException;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;

class ReverseExcelCellManager
{
    /** @var ModelMetadata */
    private $modelMetadata;

    /** @var array<string, ReverseExcelCell> Keys are property names */
    private $propertyReverseExcelCells;

    public function setModelMetadata(ModelMetadata $modelMetadata): self
    {
        $this->modelMetadata = $modelMetadata;
        return $this;
    }

    /**
     * @param array<string, ReverseExcelCell> $propertyReverseExcelCells Keys are property names
     */
    public function setPropertyReverseExcelCells(array $propertyReverseExcelCells): self
    {
        $this->propertyReverseExcelCells = $propertyReverseExcelCells;
        return $this;
    }

    /**
     * @param object $model
     *
     * @return string[]
     *
     * @throws NotGettablePropertyException
     * @throws ValueNotStringReversableException
     */
    public function reverseModelToRawPropertyModels($model): array
    {
        $rawModelData = [];
        foreach ($this->modelMetadata->getModelPropertiesMetadata() as $propertyMetadata) {
            $propertyGetterName = $propertyMetadata->getFirstDefinedGetterName();
            $rawModelData[$propertyMetadata->getColumnKey()] =
                $this->propertyReverseExcelCells[$propertyMetadata->getPropertyName()]->getReversedExcelCellValue($model->{$propertyGetterName}());
        }

        return $rawModelData;
    }
}