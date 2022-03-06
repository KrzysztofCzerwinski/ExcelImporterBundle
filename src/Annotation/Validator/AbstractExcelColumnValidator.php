<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\Target({"PROPERTY"})
 */
abstract class AbstractExcelColumnValidator extends AbstractExcelValidator
{
    /**
     * @return AbstractCellValidator;
     */
    abstract public function getRelatedValidator(): AbstractValidator;
}