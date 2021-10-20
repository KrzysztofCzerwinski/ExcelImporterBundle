<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

use Kczer\ExcelImporterBundle\Exception\Exporter\InvalidModelPropertyException;
use Kczer\ExcelImporterBundle\Exception\Exporter\NotGettablePropertyException;
use function array_filter;
use function current;

class ModelMetadata
{
    /** @var string */
    private $modelClassName;

    /** @var ModelPropertyMetadata[] Keys are column keys */
    private $modelPropertiesMetadata;


    public function getModelClassName(): string
    {
        return $this->modelClassName;
    }

    public function setModelClassName(string $modelClassName): ModelMetadata
    {
        $this->modelClassName = $modelClassName;
        return $this;
    }

    /**
     * @return ModelPropertyMetadata[] Keys are column keys
     */
    public function getModelPropertiesMetadata(): array
    {
        return $this->modelPropertiesMetadata;
    }

    /**
     * @param ModelPropertyMetadata[] $modelPropertiesMetadata
     */
    public function setModelPropertiesMetadata(array $modelPropertiesMetadata): self
    {
        $this->modelPropertiesMetadata = $modelPropertiesMetadata;

        return $this;
    }

    /**
     * @param string[] $keyMappings
     */
    public function transformColumnKeyNameKeysToExcelColumnKeys(array $keyMappings): void
    {
        foreach ($keyMappings as $columnNameKey => $excelColumnKey) {
            $this->modelPropertiesMetadata[$excelColumnKey] = $this->modelPropertiesMetadata[$columnNameKey];

            unset($this->modelPropertiesMetadata[$columnNameKey]);
        }
    }

    /**
     * @throws InvalidModelPropertyException
     * @throws NotGettablePropertyException
     */
    public function getPropertyGetterName(string $propertyName): string
    {
        /** @var ModelPropertyMetadata|false $modelPropertyMetadata */
        $modelPropertyMetadata = current(array_filter($this->modelPropertiesMetadata, static function (ModelPropertyMetadata $modelPropertyMetadata) use ($propertyName): bool {
            return $modelPropertyMetadata->getPropertyName() === $propertyName;
        }));
        if (false === $modelPropertyMetadata) {

            throw new InvalidModelPropertyException($propertyName, $this->getModelClassName());
        }

        return $modelPropertyMetadata->getFirstDefinedGetterName();
    }
}