<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation\Validator;

use Attribute;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractCellValidator;
use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;
use Doctrine\Common\Annotations\Annotation;

/**
 * @Annotation
 * @Annotation\Target({"PROPERTY"})
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
abstract class AbstractExcelColumnValidator extends AbstractExcelValidator
{
    /**
     * @return AbstractCellValidator;
     */
    abstract public function getRelatedValidator(): AbstractValidator;
}