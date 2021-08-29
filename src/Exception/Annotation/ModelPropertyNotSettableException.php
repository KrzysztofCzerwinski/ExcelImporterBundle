<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception\Annotation;

use Exception;
use Kczer\ExcelImporterBundle\Model\ModelPropertyMetadata;
use ReflectionClass;
use Throwable;
use function sprintf;

class ModelPropertyNotSettableException extends AnnotationConfigurationException
{
    public function __construct(ModelPropertyMetadata $modelPropertyMetadata, ReflectionClass $modelReflectionClass, Throwable $previous = null)
    {
        parent::__construct(
            sprintf("Property '%s' of class '%s' needs to have public setter '%s' in order to be settable",
                $modelPropertyMetadata->getPropertyName(),
                $modelReflectionClass->getName(),
                $modelPropertyMetadata->getSetterName()
            ),
            0,
            $previous
        );
    }
}