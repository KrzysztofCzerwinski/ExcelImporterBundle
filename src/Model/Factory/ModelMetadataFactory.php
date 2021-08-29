<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\Annotation\Validator\AbstractExcelColumnValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\Exception\Annotation\AnnotationConfigurationException;
use Kczer\ExcelImporterBundle\Exception\Annotation\ModelPropertyNotSettableException;
use Kczer\ExcelImporterBundle\Exception\Annotation\NotExistingModelClassException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedColumnExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\DuplicateColumnKeyException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Kczer\ExcelImporterBundle\Model\ModelPropertyMetadata;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use function array_filter;
use function array_map;
use function is_a;
use function key_exists;

class ModelMetadataFactory
{
    /** @var AnnotationReader */
    private $annotationReader;

    public function __construct()
    {
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * @throws AnnotationConfigurationException
     * @throws ExcelImportConfigurationException
     */
    public function createMetadataFromModelClass(string $modelClass): ModelMetadata
    {
        $modelReflectionClass = $this->obtainModelReflectionClass($modelClass);

        $modelPropertiesMetadata = [];
        foreach ($modelReflectionClass->getProperties() as $reflectionProperty) {
            /** @var ExcelColumn|null $excelColumn */
            $excelColumn = $this->annotationReader->getPropertyAnnotation($reflectionProperty, ExcelColumn::class);
            if (null === $excelColumn) {

                continue;
            }
            $modelPropertyMetadata = (new ModelPropertyMetadata())
                ->setExcelColumn($excelColumn)
                ->setPropertyName($reflectionProperty->getName())
                ->setValidators($this->getPropertyValidators($reflectionProperty));

            $this->validateExcelCellClass($modelPropertyMetadata)->validatePropertySettable($modelReflectionClass, $modelPropertyMetadata);
            $columnKey = $excelColumn->getColumnKey();
            if (key_exists($columnKey, $modelPropertiesMetadata)) {

                throw new DuplicateColumnKeyException($columnKey);
            }

            $modelPropertiesMetadata[$columnKey] = $modelPropertyMetadata;
        }

        return (new ModelMetadata())->setModelPropertiesMetadata($modelPropertiesMetadata);
    }

    /**
     * @throws NotExistingModelClassException
     */
    private function obtainModelReflectionClass(string $modelClass): ReflectionClass
    {
        try {

            return new ReflectionClass($modelClass);
        } catch (ReflectionException $exception) {

            throw new NotExistingModelClassException($modelClass, $exception);
        }
    }

    /**
     * @throws UnexpectedColumnExcelCellClassException
     */
    private function validateExcelCellClass(ModelPropertyMetadata $modelPropertyMetadata): self
    {
        $excelColumn = $modelPropertyMetadata->getExcelColumn();
        if (!is_a($excelColumn->getTargetExcelCellClass(), AbstractExcelCell::class, true)) {

            throw new UnexpectedColumnExcelCellClassException($excelColumn->getTargetExcelCellClass(), $modelPropertyMetadata->getPropertyName());
        }

        return $this;
    }


    /**
     * @throws ModelPropertyNotSettableException
     */
    private function validatePropertySettable(ReflectionClass $modelReflectionClass, ModelPropertyMetadata $modelPropertyMetadata): void
    {
        try {
            $setterReflection = $modelReflectionClass->getMethod($modelPropertyMetadata->getSetterName());
        } catch (ReflectionException $exception) {

            throw new ModelPropertyNotSettableException($modelPropertyMetadata, $modelReflectionClass, $exception);
        }
        if (!$setterReflection->isPublic()) {

            throw new ModelPropertyNotSettableException($modelPropertyMetadata, $modelReflectionClass);
        }

    }

    /**
     * @return AbstractValidator[]
     */
    private function getPropertyValidators(ReflectionProperty $reflectionProperty): array
    {
        /** @var AbstractExcelColumnValidator[] $excelColumnValidatorAnnotations */
        $excelColumnValidatorAnnotations = array_filter($this->annotationReader->getPropertyAnnotations($reflectionProperty), static function ($annotation) {

            return $annotation instanceof AbstractExcelColumnValidator;
        });

        return array_map(static function (AbstractExcelColumnValidator $excelColumnValidator): AbstractValidator {
            $validatorClass = $excelColumnValidator->getValidatorClass();

            return new $validatorClass($excelColumnValidator->getOptions());
        }, $excelColumnValidatorAnnotations);
    }
}