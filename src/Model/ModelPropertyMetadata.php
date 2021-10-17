<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use ReflectionMethod;
use ReflectionProperty;

class ModelPropertyMetadata
{
    /** @var string */
    public const GETTER_PREFIX = 'get';

    /** @var string */
    public const BOOL_IS_GETTER_PREFIX = 'is';

    /** @var string */
    public const BOOL_HAS_GETTER_PREFIX = 'has';

    /** @var string */
    public const SETTER_PREFIX = 'set';

    /** @var ReflectionProperty */
    private $reflectionProperty;

    /** @var ExcelColumn */
    private $excelColumn;

    /** @var string */
    private $propertyName;

    /** @var AbstractValidator[] */
    private $validators;

    /**
     * @return ReflectionProperty
     */
    public function getReflectionProperty(): ReflectionProperty
    {
        return $this->reflectionProperty;
    }

    public function setReflectionProperty(ReflectionProperty $reflectionProperty): self
    {
        $this->reflectionProperty = $reflectionProperty;
        return $this;
    }


    public function getExcelColumn(): ExcelColumn
    {
        return $this->excelColumn;
    }

    public function setExcelColumn(ExcelColumn $excelColumn): self
    {
        $this->excelColumn = $excelColumn;
        return $this;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function setPropertyName(string $propertyName): self
    {
        $this->propertyName = $propertyName;
        return $this;
    }

    public function getFirstDefinedGetterName(): ?string
    {
        $classPublicMethods = $this->reflectionProperty->getDeclaringClass()->getMethods(ReflectionMethod::IS_PUBLIC);
        $reflectionGetterMethod = $classPublicMethods[$this->getGetterName()] ?? $classPublicMethods[$this->getBoolIsGetterName()] ?? $classPublicMethods[$this->getBoolHasGetterName()] ?? null;

        return null !== $reflectionGetterMethod ? $reflectionGetterMethod->getName() : null;
    }

    public function getGetterName(): string
    {
        return sprintf('%s%s', self::GETTER_PREFIX, ucfirst($this->propertyName));
    }

    public function getBoolIsGetterName(): string
    {
        return sprintf('%s%s', self::BOOL_IS_GETTER_PREFIX, ucfirst($this->propertyName));
    }

    public function getBoolHasGetterName(): string
    {
        return sprintf('%s%s', self::BOOL_HAS_GETTER_PREFIX, ucfirst($this->propertyName));
    }

    public function getSetterName(): string
    {
        return sprintf('%s%s', self::SETTER_PREFIX, ucfirst($this->propertyName));
    }

    /**
     * @return AbstractValidator[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    public function setValidators(array $validators): ModelPropertyMetadata
    {
        $this->validators = $validators;
        return $this;
    }
}