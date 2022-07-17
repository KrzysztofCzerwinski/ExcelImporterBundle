<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractImportValidator;

/**
 * @Annotation
 * @Annotation\Target({"CLASS"})
 */
#[Attribute(Attribute::TARGET_CLASS)]
abstract class AbstractExcelImportValidator extends AbstractExcelValidator
{
    /**
     * @return AbstractImportValidator;
     */
    abstract public function getRelatedValidator(): AbstractValidator;
}