<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration;

use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Throwable;

class UnexpectedClassException extends ExcelImportConfigurationException
{
    public function __construct(string $givenClass, string $expectedClass, Throwable $previous = null, $isInterfaceExpected = false)
    {
        parent::__construct(sprintf('Class %s should %s class %s.',
            $givenClass,
            $isInterfaceExpected ? 'implement' : 'extend',
            $expectedClass
        ), 0, $previous);
    }
}