<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer\Validator\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use Kczer\ExcelImporterBundle\Annotation\Validator\AbstractExcelImportValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractImportValidator;
use ReflectionClass;
use ReflectionException;
use function array_filter;

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
            static function (AbstractExcelImportValidator $excelImportValidator): AbstractImportValidator {
                return $excelImportValidator->getRelatedValidator();
            }, array_filter(
                (new AnnotationReader())->getClassAnnotations(new ReflectionClass($modelClass)),
                static function (object $annotation): bool {
                    return $annotation instanceof AbstractExcelImportValidator;
                }
            )
        );
    }
}