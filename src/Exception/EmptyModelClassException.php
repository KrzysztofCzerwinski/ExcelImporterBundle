<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Throwable;

class EmptyModelClassException extends ExcelImportConfigurationException
{
    public function __construct(string $importerClass, Throwable $previous = null)
    {
        parent::__construct(sprintf('EXCEL import class %s has no model assicioated with it', $importerClass), 0, $previous);
    }
}