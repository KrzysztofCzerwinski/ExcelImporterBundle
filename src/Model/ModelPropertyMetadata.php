<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\Exception\Exporter\NotGettablePropertyException;
use ReflectionMethod;
use ReflectionProperty;
use function array_filter;
use function in_array;

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

    /**
     * @throws NotGettablePropertyException
     */
    public function getFirstDefinedGetterName(): string
    {
        $declaringClass = $this->reflectionProperty->getDeclaringClass();

        $reflectionGetterMethod = current(array_filter($declaringClass->getMethods(ReflectionMethod::IS_PUBLIC), function (ReflectionMethod $reflectionMethod): bool {
            return in_array($reflectionMethod->getName(), $this->getAllSupportedGetterNames(), true);
        }));
        if (false === $reflectionGetterMethod) {

            throw new NotGettablePropertyException($this, $declaringClass->getName());
        }

        return $reflectionGetterMethod->getName();
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

    /**
     * @return string[]
     */
    public function getAllSupportedGetterNames(): array
    {
        return [$this->getGetterName(), $this->getBoolIsGetterName(), $this->getBoolHasGetterName()];
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