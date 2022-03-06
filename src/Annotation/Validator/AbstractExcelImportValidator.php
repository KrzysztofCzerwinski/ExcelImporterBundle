<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Doctrine\Common\Annotations\Annotation;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractImportValidator;

/**
 * @Annotation
 * @Annotation\Target({"CLASS"})
 */
abstract class AbstractExcelImportValidator extends AbstractExcelValidator
{
    /**
     * @return AbstractImportValidator;
     */
    abstract public function getRelatedValidator(): AbstractValidator;
}