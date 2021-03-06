<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model\Factory;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;
use Kczer\ExcelImporterBundle\Exception\Annotation\SetterNotCompatibleWithExcelCellValueException;
use Kczer\ExcelImporterBundle\Model\AbstractDisplayModel;
use Kczer\ExcelImporterBundle\Model\AbstractErrorAwareModel;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Throwable;

class ModelFactory
{
    /**
     * @template T
     *
     * @param class-string<T> $modelClass
     * @param ExcelRow[] $excelRows
     * @param ModelMetadata $modelMetadata
     *
     * @return T[] Array of models associated with ModelImport class
     *
     * @throws SetterNotCompatibleWithExcelCellValueException
     */
    public function createImportedAssociatedModelsFromExcelRowsAndModelMetadata(string $modelClass, array $excelRows, ModelMetadata $modelMetadata): array
    {
        return $this->createModelsFromExcelRowsAndModelMetadata($modelClass, $excelRows, $modelMetadata);
    }

    /**
     * @param class-string<AbstractDisplayModel> $displayModelClass
     * @param ExcelRow[] $excelRows
     * @param ModelMetadata $modelMetadata
     *
     * @return AbstractDisplayModel[] Array of display models associated with ModelImport class
     *
     * @throws SetterNotCompatibleWithExcelCellValueException
     */
    public function createDisplayModelsFromExcelRowsAndModelMetadata(string $displayModelClass, array $excelRows, ModelMetadata $modelMetadata): array
    {
        return $this->createModelsFromExcelRowsAndModelMetadata($displayModelClass, $excelRows, $modelMetadata, true);
    }

    /**
     * @template T
     *
     * @param class-string<T> $modelClass
     * @param ExcelRow[] $excelRows
     * @param ModelMetadata $modelMetadata
     * @param bool $createDisplayModel
     *
     * @return T[] Array of (display) models associated with ModelImport class
     *
     * @throws SetterNotCompatibleWithExcelCellValueException
     */
    private function createModelsFromExcelRowsAndModelMetadata(
        string $modelClass,
        array $excelRows,
        ModelMetadata $modelMetadata,
        bool $createDisplayModel = false
    ): array {
        $models = [];
        foreach ($excelRows as $index => $excelRow) {
            $model = new $modelClass();
            $excelCells = $excelRow->getExcelCells();
            foreach ($modelMetadata->getModelPropertiesMetadata() as $columnKey => $modelPropertyMetadata) {
                if ($createDisplayModel && !$modelPropertyMetadata->isInDisplayModel()) {

                    continue;
                }
                $setterMethodName = $modelPropertyMetadata->getSetterName();
                $excelCell = $excelCells[$columnKey];
                $excelCellValue = $createDisplayModel ? $excelCell->getDisplayValue() : $excelCell->getValue();
                try {
                    if (null === $excelCellValue && $modelPropertyMetadata->isRequired()) {

                        continue;
                    }
                    $model->{$setterMethodName}($excelCellValue);
                } catch (Throwable $exception) {

                    throw new SetterNotCompatibleWithExcelCellValueException($modelClass, $excelCell, $modelPropertyMetadata, $exception);
                }
            }
            if ($model instanceof AbstractDisplayModel || $model instanceof AbstractErrorAwareModel) {
                $model
                    ->setMergedAllErrorMessages($excelRow->getMergedAllErrorMessages())
                    ->setValid(!$excelRow->hasErrors())
                ;
            }
            $models[$index] = $model;
        }

        return $models;
    }
}