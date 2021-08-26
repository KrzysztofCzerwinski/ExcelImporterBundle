<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model\Factory;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;
use Kczer\ExcelImporterBundle\Exception\Annotation\SetterNotCompatibleWithExcelCellValueException;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Throwable;

class ModelFactory
{
    /**
     * @param string $modelClass
     * @param ExcelRow[] $excelRows
     * @param ModelMetadata $modelMetadata
     *
     * @return array Array of models associated with ModelImport class
     *
     * @throws SetterNotCompatibleWithExcelCellValueException
     */
    public function createModelsFromExcelRowsAndModelMetadata(string $modelClass, array $excelRows, ModelMetadata $modelMetadata): array
    {
        $models = [];
        foreach ($excelRows as $excelRow) {
            $model = new $modelClass();
            $excelCells = $excelRow->getExcelCells();
            foreach ($modelMetadata->getModelPropertiesMetadata() as $columnKey => $modelPropertyMetadata) {
                $setterMethodName = $modelPropertyMetadata->getSetterName();
                $excelCell = $excelCells[$columnKey];
                try {
                    $excelCellValue = $excelCell->getValue();
                    if (null !== $excelCellValue) {
                        $model->{$setterMethodName}($excelCellValue);
                    }
                } catch (Throwable $exception) {

                    throw new SetterNotCompatibleWithExcelCellValueException($excelCell, $modelPropertyMetadata, $exception);
                }
            }

            $models[] = $model;
        }

        return $models;
    }
}