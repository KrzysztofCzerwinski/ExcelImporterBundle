<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model\Factory;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidAnnotationParamException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedAnnotationOptionException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionExpectedDataTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionValueDataTypeException;
use Kczer\ExcelImporterBundle\Model\ModelPropertyMetadata;
use ReflectionProperty;
use function compact;

class ModelPropertyMetadataFactory
{
    /**
     * @param AbstractCellValidator[] $validators
     */
    public function createModelPropertyMetadata(
        string              $columnKey,
        ?ReflectionProperty $reflectionProperty,
        ?ExcelColumn        $excelColumn,
        array               $validators,
        bool                $isInDisplayModel

    ): ModelPropertyMetadata {
        return (new ModelPropertyMetadata())
            ->setReflectionProperty($reflectionProperty)
            ->setExcelColumn($excelColumn)
            ->setColumnKey($columnKey)
            ->setPropertyName($reflectionProperty->getName())
            ->setInDisplayModel($isInDisplayModel)
            ->setValidators($validators)
        ;
    }

    /**
     * @throws UnexpectedAnnotationOptionException
     * @throws UnexpectedOptionValueDataTypeException
     * @throws UnexpectedOptionExpectedDataTypeException
     * @throws InvalidAnnotationParamException
     */
    public function createForNonExistingProperty(
        string $columnKey,
        string $cellName,
        string $propertyName,
        string $targetExcelCellClass,
        bool   $required,
        bool   $isInDisplayModel
    ): ModelPropertyMetadata {
        return (new ModelPropertyMetadata())
            ->setColumnKey($columnKey)
            ->setPropertyName($propertyName)
            ->setInDisplayModel($isInDisplayModel)
            ->setExcelColumn(
                new ExcelColumn(compact(
                    'cellName',
                    'targetExcelCellClass',
                    'required',
                    'columnKey'
                ))
            )
        ;
    }
}