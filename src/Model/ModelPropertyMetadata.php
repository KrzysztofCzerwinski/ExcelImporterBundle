<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\Exception\Exporter\NotGettablePropertyException;
use ReflectionException;
use ReflectionMethod;
use ReflectionProperty;
use function array_filter;
use function in_array;
use function ucfirst;

class ModelPropertyMetadata
{
    public const GETTER_PREFIX = 'get';

    public const BOOL_IS_GETTER_PREFIX = 'is';

    public const BOOL_HAS_GETTER_PREFIX = 'has';

    public const SETTER_PREFIX = 'set';

    /** @var ReflectionProperty|null */
    private $reflectionProperty;

    /** @var ExcelColumn|null */
    private $excelColumn;

    /** @var string|null */
    private $columnKey;

    /** @var string */
    private $propertyName;

    /** @var bool */
    private $inDisplayModel = true;

    /** @var AbstractCellValidator[] */
    private $validators;

    public function setReflectionProperty(?ReflectionProperty $reflectionProperty): self
    {
        $this->reflectionProperty = $reflectionProperty;

        return $this;
    }

    public function getExcelColumn(): ?ExcelColumn
    {
        return $this->excelColumn;
    }

    public function setExcelColumn(?ExcelColumn $excelColumn): self
    {
        $this->excelColumn = $excelColumn;
        return $this;
    }

    public function getColumnKey(): ?string
    {
        return $this->columnKey;
    }

    public function setColumnKey(?string $columnKey): self
    {
        $this->columnKey = $columnKey;

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

    public function isInDisplayModel(): bool
    {
        return $this->inDisplayModel;
    }

    public function setInDisplayModel(bool $inDisplayModel): self
    {
        $this->inDisplayModel = $inDisplayModel;

        return $this;
    }

    /**
     * @return AbstractCellValidator[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    public function setValidators(array $validators): self
    {
        $this->validators = $validators;
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

    /**
     * @throws ReflectionException
     */
    public function getTypeAppropriateGetterName(): string
    {
        return 'bool' === $this->getExpectedType() ? $this->getBoolIsGetterName() : $this->getGetterName();
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
     * @return array{string, string, string}
     */
    public function getAllSupportedGetterNames(): array
    {
        return [$this->getGetterName(), $this->getBoolIsGetterName(), $this->getBoolHasGetterName()];
    }

    /**
     * @return class-string|string|null
     *
     * @throws ReflectionException
     */
    public function getExpectedType(): ?string
    {
        $returnType = (new ReflectionMethod($this->getExcelColumn()->getTargetExcelCellClass(), 'getParsedValue'))->getReturnType();

        return null !== $returnType ? $returnType->getName() : null;
    }

    public function getSetterName(): string
    {
        return sprintf('%s%s', self::SETTER_PREFIX, ucfirst($this->propertyName));
    }
}