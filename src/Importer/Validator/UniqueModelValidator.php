<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer\Validator;

use Kczer\ExcelImporterBundle\Exception\Exporter\InvalidModelPropertyException;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use function array_diff;
use function array_flip;
use function array_intersect_key;
use function array_keys;
use function array_map;
use function implode;
use function in_array;

class UniqueModelValidator extends AbstractImportValidator
{
    /** @var string[] */
    private $fields;

    /** @var string[][] */
    private $nonUniqueValues = [];

    /**
     * @param string[] $fields
     */
    public function __construct(string $message, array $fields)
    {
        parent::__construct($message);
        $this->fields = $fields;
    }

    /**
     * @return string[][]
     */
    public function getNonUniqueValues(): array
    {
        return $this->nonUniqueValues;
    }

    public static function getDefaultMessage(): string
    {
        return 'excel_importer.validator.messages.unique_validator_default_message';
    }

    /**
     * @inheritDoc
     *
     * @throws InvalidModelPropertyException
     */
    public function isImportValid(array $excelRows, ModelMetadata $modelMetadata): bool
    {
        $propertyColumnKeyMappings = array_intersect_key(
            $this->getPropertyExcelColumnKeyMappings($modelMetadata),
            array_flip($this->fields)
        );

        $unmappedProperties = array_diff($this->fields, array_keys($propertyColumnKeyMappings));
        if (!empty($unmappedProperties)) {

            throw new InvalidModelPropertyException($unmappedProperties, $modelMetadata->getModelClassName());
        }

        $values = [];
        foreach ($excelRows as $excelRow) {
            $currentValues = array_map([$excelRow, 'getExcelCellRawValue'], $propertyColumnKeyMappings);
            if (!in_array($currentValues, $values, true)) {
                $values[] = $currentValues;

                continue;
            }

            if (!in_array($currentValues, $this->nonUniqueValues, true)) {
                $this->nonUniqueValues[] = $currentValues;
            }
        }

        return empty($this->nonUniqueValues);
    }

    /**
     * @inheritDoc
     */
    protected function getReplaceablePropertiesAsParams(): array
    {
        $nonUniqueValues = array_map(static function (array $nonUniqueValue): string {
            return '(' . implode(', ', $nonUniqueValue) . ')';
        }, $this->nonUniqueValues);

        return [
            '%fields%' => implode(', ', $this->fields),
            '%nonUniqueValues%' => implode(', ', $nonUniqueValues),
        ];
    }

}