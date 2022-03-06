<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator;

use Kczer\ExcelImporterBundle\Importer\Validator\AbstractValidator;

abstract class AbstractCellValidator extends AbstractValidator
{
    /**
     * @return bool True, if value is valid, false otherwise
     */
    public abstract function isExcelCellValueValid(string $rawValue): bool;
}