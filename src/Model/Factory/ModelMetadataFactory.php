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
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use function array_filter;
use function array_map;
use function is_a;

class ModelMetadataFactory
{
    public function __construct(
        private ModelPropertyMetadataFactory $modelPropertyMetadataFactory,
        private AnnotationReader             $annotationReader,
    ) {
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

        $propertyIndex = 1;
        $modelMetadata = (new ModelMetadata())->setModelClassName($modelReflectionClass->getName());
        foreach ($modelReflectionClass->getProperties() as $reflectionProperty) {
            $excelColumn =
                ($reflectionProperty->getAttributes(ExcelColumn::class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null)?->newInstance() ??
                $this->annotationReader->getPropertyAnnotation($reflectionProperty, ExcelColumn::class);
            if (null === $excelColumn) {

                continue;
            }
            $isPropertyInDisplayModel = $isDisplayModelClassDefined && $displayModelReflectionClass->hasProperty($reflectionProperty->getName());
            $modelPropertyMetadata = $this->modelPropertyMetadataFactory->createModelPropertyMetadata(
                $reflectionProperty,
                $excelColumn,
                $this->getPropertyValidators($reflectionProperty),
                $isPropertyInDisplayModel,
                $propertyIndex
            );
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

            $modelMetadata->addModelPropertyMetadata($modelPropertyMetadata);

            $propertyIndex++;
        }

        return $modelMetadata;
    }

    public function createMetadataForNonExistingClass(string $className): ModelMetadata
    {
        return (new ModelMetadata())
            ->setModelClassName($className)
        ;
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
        if (!is_a($modelPropertyMetadata->getTargetExcelCellClass(), AbstractExcelCell::class, true)) {

            throw new UnexpectedColumnExcelCellClassException($modelPropertyMetadata->getTargetExcelCellClass(), $modelPropertyMetadata->getPropertyName());
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
        $excelColumnValidatorReflectionAttributes = $reflectionProperty->getAttributes(
            AbstractExcelColumnValidator::class,
            ReflectionAttribute::IS_INSTANCEOF
        );
        if (!empty($excelColumnValidatorReflectionAttributes)) {

            return array_map(
                static function (ReflectionAttribute $reflectionAttribute): AbstractCellValidator {
                    /** @var AbstractExcelColumnValidator $excelColumnValidator */
                    $excelColumnValidator = $reflectionAttribute->newInstance();

                    return $excelColumnValidator->getRelatedValidator();
                },
                $excelColumnValidatorReflectionAttributes
            );
        }

        /** @var AbstractExcelColumnValidator[] $excelColumnValidatorAnnotations */
        $excelColumnValidatorAnnotations = array_filter(
            $this->annotationReader->getPropertyAnnotations($reflectionProperty),
            static fn($annotation): bool => $annotation instanceof AbstractExcelColumnValidator
        );

        return array_map(
            static fn(AbstractExcelColumnValidator $excelColumnValidator): AbstractCellValidator => $excelColumnValidator->getRelatedValidator(),
            $excelColumnValidatorAnnotations
        );
    }
}