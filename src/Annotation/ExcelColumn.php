<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation;

use Doctrine\Common\Annotations\Annotation;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidAnnotationParamException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedAnnotationOptionException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionExpectedDataTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionValueDataTypeException;
use function is_bool;
use function preg_match;
use function trim;

/**
 * @Annotation
 * @Annotation\Target({"PROPERTY"})
 */
class ExcelColumn extends AbstractOptionsAnnotation
{
    /**
     * Column name
     *
     * @Annotation\Required()
     *
     * @var string
     */
    private $cellName;

    /**
     * Fully qualified ExcelCell class
     *
     * @Annotation\Required()
     *
     * @var class-string<AbstractExcelCell>
     */
    private $targetExcelCellClass;

    /**
     * Excel column key in A-Z notation or human-readable name from Excel header.
     * Can also be constant field id like A3, C10 etc.
     *
     * @Annotation\Required()
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
     * @param array{cellName: string, targetExcelCellClass: string, required: bool, columnKey: string, options: array} $annotationData
     *
     * @throws InvalidAnnotationParamException
     * @throws UnexpectedAnnotationOptionException
     * @throws UnexpectedOptionValueDataTypeException
     * @throws UnexpectedOptionExpectedDataTypeException
     */
    public function __construct(array $annotationData)
    {
        parent::__construct($annotationData);
        $required = $annotationData['required'] ?? true;
        if (!is_bool($required)) {

            throw new InvalidAnnotationParamException('required', static::class, $required, 'bool');
        }
        $this->targetExcelCellClass = $annotationData['targetExcelCellClass'];
        $this->columnKey = $annotationData['columnKey'];
        $this->cellName = $annotationData['cellName'] ?? '';
        $this->required = $required;
    }

    protected function getSupportedOptions(): array
    {
        return [
            'reverseDateTimeFormat' => 'string',
        ];
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

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getReverseReverseDateTimeFormat(): ?string
    {
        return $this->getOptions()['reverseDateTimeFormat'] ?? null;
    }
}