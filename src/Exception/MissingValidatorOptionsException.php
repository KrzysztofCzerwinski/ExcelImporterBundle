<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Throwable;
use function sprintf;

class MissingValidatorOptionsException extends ExcelImportConfigurationException
{
    /**
     * @param string[] $optionNames
     * @param Throwable|null $previous
     */
    public function __construct(array $optionNames, Throwable $previous = null)
    {
        parent::__construct(sprintf("Missing configuration options: %s", implode(', ', $optionNames)), 0, $previous);
    }
}