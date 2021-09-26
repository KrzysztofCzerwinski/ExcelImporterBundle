<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;


use function array_map;
use function in_array;

class ModelMetadata
{
    /** @var ModelPropertyMetadata[] Keys are column keys */
    private $modelPropertiesMetadata;

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
}