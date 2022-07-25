<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

use JetBrains\PhpStorm\ArrayShape;
use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\Exception\Exporter\NotGettablePropertyException;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use function array_filter;
use function in_array;
use function ucfirst;

class ModelPropertyMetadata
{
    public const GETTER_PREFIX = 'get';

    public const ISSER_PREFIX = 'is';

    public const HASSER_PREFIX = 'has';

    public const SETTER_PREFIX = 'set';

    private ?ReflectionProperty $reflectionProperty = null;

    private ?ExcelColumn $excelColumn;

    private ?string $columnKey;

    private string $propertyName;

    private bool $inDisplayModel = true;

    /** @var AbstractCellValidator[] */
    private array $validators;

    /** @var class-string<AbstractExcelCell>|null */
    private ?string $targetExcelCellClass = null;

    private ?bool $required = null;

    public function setReflectionProperty(?ReflectionProperty $reflectionProperty): static
    {
        $this->reflectionProperty = $reflectionProperty;

        return $this;
    }

    public function getExcelColumn(): ?ExcelColumn
    {
        return $this->excelColumn;
    }

    public function setExcelColumn(?ExcelColumn $excelColumn): static
    {
        $this->excelColumn = $excelColumn;
        return $this;
    }

    public function getColumnKey(): ?string
    {
        return $this->columnKey;
    }

    public function setColumnKey(?string $columnKey): static
    {
        $this->columnKey = $columnKey;

        return $this;
    }

    public function getPropertyName(): string
    {
        return $this->propertyName;
    }

    public function setPropertyName(string $propertyName): static
    {
        $this->propertyName = $propertyName;
        return $this;
    }

    public function isInDisplayModel(): bool
    {
        return $this->inDisplayModel;
    }

    public function setInDisplayModel(bool $inDisplayModel): static
    {
        $this->inDisplayModel = $inDisplayModel;

        return $this;
    }

    public function getTargetExcelCellClass(): ?string
    {
        return $this->targetExcelCellClass;
    }

    public function setTargetExcelCellClass(string $targetExcelCellClass): static
    {
        $this->targetExcelCellClass = $targetExcelCellClass;

        return $this;
    }

    public function isRequired(): ?bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): static
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @return AbstractCellValidator[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    public function setValidators(array $validators): static
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
        return sprintf('%s%s', self::ISSER_PREFIX, ucfirst($this->propertyName));
    }

    public function getBoolHasGetterName(): string
    {
        return sprintf('%s%s', self::HASSER_PREFIX, ucfirst($this->propertyName));
    }

    #[ArrayShape(['string', 'string', 'string'])]
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
        // Metadata can come from maker or already existing model
        $targetExcelCellClass = $this->getTargetExcelCellClass() ?? $this->getExcelColumn()->getTargetExcelCellClass();

        $returnType = (new ReflectionMethod($targetExcelCellClass, 'getParsedValue'))->getReturnType();

        return $returnType instanceof ReflectionNamedType ? $returnType->getName() : null;
    }

    public function getSetterName(): string
    {
        return sprintf('%s%s', self::SETTER_PREFIX, ucfirst($this->propertyName));
    }
}