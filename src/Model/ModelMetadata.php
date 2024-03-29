<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

use Kczer\ExcelImporterBundle\Exception\DuplicateExcelIdentifierException;
use Kczer\ExcelImporterBundle\Exception\Exporter\InvalidModelPropertyException;
use Kczer\ExcelImporterBundle\Exception\Exporter\NotGettablePropertyException;
use function array_change_key_case;
use function array_filter;
use function current;

class ModelMetadata
{
    /** @var class-string */
    private $modelClassName;

    /** @var array<string, ModelPropertyMetadata> Keys are excel identifiers */
    private $modelPropertiesMetadata = [];


    public function getModelClassName(): string
    {
        return $this->modelClassName;
    }

    /**
     * @param class-string $modelClassName
     */
    public function setModelClassName(string $modelClassName): self
    {
        $this->modelClassName = $modelClassName;
        return $this;
    }

    /**
     * @return array<string, ModelPropertyMetadata> Keys are column identifiers
     */
    public function getModelPropertiesMetadata(): array
    {
        return $this->modelPropertiesMetadata;
    }

    /**
     * @param ModelPropertyMetadata[] $modelPropertiesMetadata
     *
     * @throws DuplicateExcelIdentifierException
     */
    public function setModelPropertiesMetadata(array $modelPropertiesMetadata): self
    {
        $this->modelPropertiesMetadata = [];
        foreach ($modelPropertiesMetadata as $modelPropertyMetadata) {
            $this->addModelPropertyMetadata($modelPropertyMetadata);
        }

        return $this;
    }

    public function hasProperty(string $propertyName): bool
    {
        return !empty(
            array_filter($this->modelPropertiesMetadata, static function (ModelPropertyMetadata $modelPropertyMetadata) use ($propertyName): bool {
                return $propertyName === $modelPropertyMetadata->getPropertyName();
            })
        );
    }

    /**
     * @throws DuplicateExcelIdentifierException
     */
    public function addModelPropertyMetadata(ModelPropertyMetadata $modelPropertyMetadata): self
    {
        $columnIdentifier = $modelPropertyMetadata->getColumnKey();

        if (key_exists($columnIdentifier, $this->modelPropertiesMetadata)) {

            throw new DuplicateExcelIdentifierException($columnIdentifier);
        }
        $this->modelPropertiesMetadata[$columnIdentifier] = $modelPropertyMetadata;

        return $this;
    }

    /**
     * @param string[] $keyMappings
     */
    public function transformColumnKeyNameKeysToExcelColumnKeys(array $keyMappings): void
    {
        $keyLoweredModelPropertiesMetadata = array_change_key_case($this->modelPropertiesMetadata);
        foreach ($keyMappings as $columnNameKey => $excelColumnKey) {
            $this->modelPropertiesMetadata[$excelColumnKey] = $keyLoweredModelPropertiesMetadata[$columnNameKey];
        }
        $this->modelPropertiesMetadata = array_diff_ukey(
            $this->modelPropertiesMetadata,
            $keyMappings,
            'strcasecmp'
        );
    }

    /**
     * @throws InvalidModelPropertyException
     * @throws NotGettablePropertyException
     */
    public function getPropertyGetterName(string $propertyName): string
    {
        return $this->getPropertyMetadataByName($propertyName)->getFirstDefinedGetterName();
    }

    /**
     * @throws InvalidModelPropertyException
     */
    public function getPropertyMetadataByName(string $propertyName): ModelPropertyMetadata
    {
        $modelPropertyMetadata = current(
            array_filter(
                $this->modelPropertiesMetadata,
                static function (ModelPropertyMetadata $modelPropertyMetadata) use ($propertyName): bool {
                    return $modelPropertyMetadata->getPropertyName() === $propertyName;
                })
        );
        if (false === $modelPropertyMetadata) {

            throw new InvalidModelPropertyException($propertyName, $this->getModelClassName());
        }

        return $modelPropertyMetadata;
    }
}
