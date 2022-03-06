<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelRow;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use Kczer\ExcelImporterBundle\Model\ModelPropertyMetadata;
use function array_flip;
use function array_map;

abstract class AbstractImportValidator extends AbstractValidator
{
    /**
     * @param ExcelRow[] $excelRows
     */
    abstract public function isImportValid(array $excelRows, ModelMetadata $modelMetadata): bool;

    /**
     * @return array<string, string> Keys are property names, values are column identifiers
     */
    protected function getPropertyExcelColumnKeyMappings(ModelMetadata $modelMetadata): array
    {
        return array_flip(
            array_map(static function (ModelPropertyMetadata $modelPropertyMetadata): string {
                return $modelPropertyMetadata->getPropertyName();
            }, $modelMetadata->getModelPropertiesMetadata())
        );
    }
}