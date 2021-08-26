<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;
use Doctrine\Common\Annotations\Annotation\Required;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidAnnotationParamException;
use function is_bool;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ExcelColumn
{
    /**
     * Column name
     *
     * @Required()
     *
     * @var string
     */
    private $cellName;

    /**
     * Fully qualified ExcelCell class
     *
     * @Required()
     *
     * @var string
     */
    private $targetExcelCellClass;

    /**
     * @Required()
     *
     * @var string
     */
    private $columnKey;

    /**
     * Whether column cells are required or not
     *
     * @var bool
     */
    private $required;


    /**
     * @throws InvalidAnnotationParamException
     */
    public function __construct(array $annotationData)
    {
        $this->targetExcelCellClass = $annotationData['targetExcelCellClass'];

        $this->columnKey = $annotationData['columnKey'];
        $this->cellName = $annotationData['cellName'] ?? '';

        $required = $annotationData['required'] ?? true;
        if (!is_bool($required)) {

           throw new InvalidAnnotationParamException('required', static::class, $required, 'bool');
        }
        $this->required = $annotationData['required'] ?? true;
    }

    public function getCellName(): string
    {
        return $this->cellName;
    }

    public function getTargetExcelCellClass(): string
    {
        return $this->targetExcelCellClass;
    }

    public function getColumnKey(): string
    {
        return $this->columnKey;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

}