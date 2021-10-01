<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Kczer\ExcelImporterBundle\Model\AbstractDisplayModel;
use Throwable;

class UnexpectedDisplayModelClassException extends ExcelImportConfigurationException
{
    public function __construct(string $displayModelClass, Throwable $previous = null)
    {
        parent::__construct(sprintf('Display model class %s must extend %s', $displayModelClass, AbstractDisplayModel::class), 0, $previous);
    }
}