<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use Throwable;

class UnexpectedValidatorClassException extends UnexpectedClassException
{
    public function __construct(string $givenClass, Throwable $previous = null)
    {
        parent::__construct($givenClass, AbstractValidator::class, $previous, true);
    }
}