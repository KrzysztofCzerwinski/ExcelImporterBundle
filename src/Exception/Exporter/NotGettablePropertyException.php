<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Exporter;

use Exception;
use Kczer\ExcelImporterBundle\Model\ModelPropertyMetadata;
use Throwable;
use function sprintf;

class NotGettablePropertyException extends Exception
{
    public function __construct(ModelPropertyMetadata $modelPropertyMetadata, string $className, Throwable $previous = null)
    {
        parent::__construct(sprintf(
            'Neither of the methods %s exist and have public access in %s',
            implode(', ', $modelPropertyMetadata->getAllSupportedGetterNames()),
            $className
        ), 0, $previous);
    }
}