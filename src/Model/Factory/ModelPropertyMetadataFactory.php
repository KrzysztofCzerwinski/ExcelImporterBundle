<?php
/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model\Factory;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\StringExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidAnnotationParamException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedAnnotationOptionException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionExpectedDataTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionValueDataTypeException;
use Kczer\ExcelImporterBundle\Model\ModelPropertyMetadata;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use function array_flip;
use function array_intersect_key;
use function array_map;
use function current;

class ModelPropertyMetadataFactory
{
    /**
     * @param array<string, class-string<AbstractExcelCell>> $typeMappings
     */
    public function __construct(
        private array $typeMappings,
    ) {
    }

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
            ->setTargetExcelCellClass($this->resolveTargetExcelCellClass($excelColumn, $reflectionProperty))
            ->setRequired($this->resolveColumnRequired($excelColumn, $reflectionProperty))
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
                new ExcelColumn(
                    $columnKey,
                    targetExcelCellClass: $targetExcelCellClass,
                    columnKey: $columnKey,
                    cellName: $cellName,
                    required: $required
                )
            )
        ;
    }

    private function resolveTargetExcelCellClass(ExcelColumn $excelColumn, ReflectionProperty $reflectionProperty): string
    {
        if (null !== $excelColumn->getTargetExcelCellClass()) {

            return $excelColumn->getTargetExcelCellClass();
        }

        $reflectionType = $reflectionProperty->getType();
        /** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */
        if ($reflectionType instanceof ReflectionIntersectionType) {

            return StringExcelCell::class;
        }

        $types = ($reflectionType instanceof ReflectionUnionType) ?
            array_flip(array_map(
                static fn(ReflectionNamedType $reflectionNamedType): string => $reflectionNamedType->getName(),
                $reflectionType->getTypes()
            )) :
            [$reflectionType?->getName() => true];

        return current(array_intersect_key($this->typeMappings, $types)) ?: StringExcelCell::class;
    }

    private function resolveColumnRequired(ExcelColumn $excelColumn, ReflectionProperty $reflectionProperty): bool
    {
        if (null !== $excelColumn->isRequired()) {

            return $excelColumn->isRequired();
        }

        return !($reflectionProperty->getType()?->allowsNull() ?? true);
    }
}