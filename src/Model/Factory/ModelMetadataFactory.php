<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\Annotation\Validator\AbstractExcelColumnValidator;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidDisplayModelSetterParameterTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\ModelPropertyNotSettableException;
use Kczer\ExcelImporterBundle\Exception\Annotation\NotExistingModelClassException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedColumnExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\DuplicateExcelIdentifierException;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Kczer\ExcelImporterBundle\Model\ModelPropertyMetadata;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use Symfony\Contracts\Translation\TranslatorInterface;
use function array_filter;
use function array_map;
use function is_a;

class ModelMetadataFactory
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var AnnotationReader */
    private $annotationReader;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        $this->annotationReader = new AnnotationReader();
    }

    /**
     * @param string $modelClass
     * @param string|null $displayModelClass
     *
     * @return ModelMetadata
     *
     * @throws DuplicateExcelIdentifierException
     * @throws InvalidDisplayModelSetterParameterTypeException
     * @throws ModelPropertyNotSettableException
     * @throws NotExistingModelClassException
     * @throws ReflectionException
     * @throws UnexpectedColumnExcelCellClassException
     */
    public function createMetadataFromModelClass(string $modelClass, ?string $displayModelClass): ModelMetadata
    {
        $modelReflectionClass = $this->obtainModelReflectionClass($modelClass);
        $isDisplayModelClassDefined = null !== $displayModelClass;
        $displayModelReflectionClass = $isDisplayModelClassDefined ? $this->obtainModelReflectionClass($displayModelClass) : null;

        $modelMetadata = (new ModelMetadata())->setModelClassName($modelReflectionClass->getName());
        foreach ($modelReflectionClass->getProperties() as $reflectionProperty) {
            $excelColumn = $this->annotationReader->getPropertyAnnotation($reflectionProperty, ExcelColumn::class);
            if (null === $excelColumn) {

                continue;
            }
            $isPropertyInDisplayModel = $isDisplayModelClassDefined && $displayModelReflectionClass->hasProperty($reflectionProperty->getName());
            $columnKey = $this->translator->trans($excelColumn->getColumnKey());
            $modelPropertyMetadata = (new ModelPropertyMetadata())
                ->setReflectionProperty($reflectionProperty)
                ->setExcelColumn($excelColumn)
                ->setColumnKey($columnKey)
                ->setPropertyName($reflectionProperty->getName())
                ->setInDisplayModel($isPropertyInDisplayModel)
                ->setValidators($this->getPropertyValidators($reflectionProperty))
            ;
            $this
                ->validateExcelCellClass($modelPropertyMetadata)
                ->validatePropertySettable($modelReflectionClass, $modelPropertyMetadata)
            ;

            if ($isPropertyInDisplayModel) {
                $this
                    ->validatePropertySettable($displayModelReflectionClass, $modelPropertyMetadata)
                    ->validateDisplayModelSetterType($displayModelReflectionClass, $modelPropertyMetadata)
                ;
            }

            $modelMetadata->addModelPropertyMetadata($columnKey, $modelPropertyMetadata);
        }

        return $modelMetadata;
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
    private function validatePropertySettable(ReflectionClass $modelReflectionClass, ModelPropertyMetadata $modelPropertyMetadata): self
    {
        try {
            $setterReflection = $modelReflectionClass->getMethod($modelPropertyMetadata->getSetterName());
        } catch (ReflectionException $exception) {

            throw new ModelPropertyNotSettableException($modelPropertyMetadata, $modelReflectionClass, $exception);
        }
        if (!$setterReflection->isPublic()) {

            throw new ModelPropertyNotSettableException($modelPropertyMetadata, $modelReflectionClass);
        }

        return $this;
    }

    /**
     * @throws ReflectionException
     * @throws InvalidDisplayModelSetterParameterTypeException
     */
    private function validateDisplayModelSetterType(ReflectionClass $modelReflectionClass, ModelPropertyMetadata $displayModelPropertyMetadata): void
    {
        $setterReflection = $modelReflectionClass->getMethod($displayModelPropertyMetadata->getSetterName());
        $reflectionSetterParameter = current($setterReflection->getParameters());

        $reflectionSetterParameterType = false !== $reflectionSetterParameter ? $reflectionSetterParameter->getType() : null;
        $reflectionSetterParameterTypeName = null !== $reflectionSetterParameterType ? $reflectionSetterParameterType->getName() : 'string';
        if ('string' !== $reflectionSetterParameterTypeName) {

            throw new InvalidDisplayModelSetterParameterTypeException($modelReflectionClass->getName(), $setterReflection->getName(), $reflectionSetterParameter->getName(), $reflectionSetterParameterTypeName);
        }
    }

    /**
     * @return AbstractCellValidator[]
     */
    private function getPropertyValidators(ReflectionProperty $reflectionProperty): array
    {
        /** @var AbstractExcelColumnValidator[] $excelColumnValidatorAnnotations */
        $excelColumnValidatorAnnotations = array_filter($this->annotationReader->getPropertyAnnotations($reflectionProperty), static function ($annotation): bool {

            return $annotation instanceof AbstractExcelColumnValidator;
        });

        return array_map(static function (AbstractExcelColumnValidator $excelColumnValidator): AbstractCellValidator {

            return $excelColumnValidator->getRelatedValidator();
        }, $excelColumnValidatorAnnotations);
    }
}