<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Throwable;

class EmptyModelClassException extends ExcelImportConfigurationException
{
    public function __construct(Throwable $previous = null)
    {
        parent::__construct('EXCEL import class has no model associated with it', 0, $previous);
    }
}