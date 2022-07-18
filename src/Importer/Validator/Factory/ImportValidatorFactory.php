<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer\Validator\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Kczer\ExcelImporterBundle\Annotation\Validator\AbstractExcelImportValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractImportValidator;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use function array_filter;
use function array_map;

class ImportValidatorFactory
{
    /**
     * @param class-string $modelClass
     *
     * @return AbstractImportValidator[]
     *
     * @throws ReflectionException
     */
    public function createFromImportModelClass(string $modelClass): array
    {
        return array_map(
            static fn(AbstractExcelImportValidator $excelImportValidator): AbstractImportValidator => $excelImportValidator->getRelatedValidator(),
            $this->getImportValidatorsMetadata($modelClass)
        );
    }

    /**
     * @param class-string $modelClass
     *
     * @return AbstractExcelImportValidator[]
     *
     * @throws ReflectionException
     */
    private function getImportValidatorsMetadata(string $modelClass): array
    {
        $reflectionClass = new ReflectionClass($modelClass);

        $excelImportValidatorReflectionAttributes = $reflectionClass->getAttributes(AbstractExcelImportValidator::class, ReflectionAttribute::IS_INSTANCEOF);
        if (!empty($excelImportValidatorReflectionAttributes)) {

            return array_map(
                static fn(ReflectionAttribute $reflectionAttribute): AbstractExcelImportValidator => $reflectionAttribute->newInstance(),
                $excelImportValidatorReflectionAttributes
            );
        }

        return array_filter(
            (new AnnotationReader())->getClassAnnotations($reflectionClass),
            static fn(object $annotation): bool => $annotation instanceof AbstractExcelImportValidator
        );
    }
}
