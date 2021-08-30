<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model\Factory;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;
use Kczer\ExcelImporterBundle\Exception\Annotation\SetterNotCompatibleWithExcelCellValueException;
use Kczer\ExcelImporterBundle\Model\DisplayModelInterface;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Throwable;

class ModelFactory
{
    public const DISPLAY_MODE_ERROR_MESSAGE_SETTER_NAME = 'setAllMergedErrorMessages';

    /**
     * @param string $modelClass
     * @param ExcelRow[] $excelRows
     * @param ModelMetadata $modelMetadata
     *
     * @return array Array of models associated with ModelImport class
     *
     * @throws SetterNotCompatibleWithExcelCellValueException
     */
    public function createImportedAssociatedModelsFromExcelRowsAndModelMetadata(string $modelClass, array $excelRows, ModelMetadata $modelMetadata): array
    {
        return $this->createModelsFromExcelRowsAndModelMetadata($modelClass, $excelRows, $modelMetadata, false);
    }

    /**
     * @param string $displayModelClass
     * @param ExcelRow[] $excelRows
     * @param ModelMetadata $modelMetadata
     *
     * @return DisplayModelInterface[] Array of display models associated with ModelImport class
     *
     * @throws SetterNotCompatibleWithExcelCellValueException
     */
    public function createDisplayModelsFromExcelRowsAndModelMetadata(string $displayModelClass, array $excelRows, ModelMetadata $modelMetadata): array
    {
        return $this->createModelsFromExcelRowsAndModelMetadata($displayModelClass, $excelRows, $modelMetadata, true);
    }

    /**
     * @param string $modelClass
     * @param ExcelRow[] $excelRows
     * @param ModelMetadata $modelMetadata
     * @param bool $createDisplayModel
     *
     * @return array|DisplayModelInterface[] Array of display models associated with ModelImport class
     *
     * @throws SetterNotCompatibleWithExcelCellValueException
     */
    private function createModelsFromExcelRowsAndModelMetadata(string $modelClass, array $excelRows, ModelMetadata $modelMetadata, bool $createDisplayModel = false): array
    {
        $models = [];
        foreach ($excelRows as $excelRow) {
            $model = new $modelClass();
            $excelCells = $excelRow->getExcelCells();
            foreach ($modelMetadata->getModelPropertiesMetadata() as $columnKey => $modelPropertyMetadata) {
                $setterMethodName = $modelPropertyMetadata->getSetterName();
                $excelCell = $excelCells[$columnKey];
                $excelCellValue = $createDisplayModel ? $excelCell->getDisplayValue() : $excelCell->getValue();
                try {
                    if (null !== $excelCellValue) {
                        $model->{$setterMethodName}($excelCellValue);
                    }
                } catch (Throwable $exception) {

                    throw new SetterNotCompatibleWithExcelCellValueException($modelClass, $excelCell, $modelPropertyMetadata, $exception);
                }
            }
            if ($model instanceof DisplayModelInterface) {
                $model->setAllMergedErrorMessages($excelRow->getMergedAllErrorMessages());
            }
            $models[] = $model;
        }

        return $models;
    }
}