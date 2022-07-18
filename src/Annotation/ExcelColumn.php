<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Annotation;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\DateTimeExcelCell;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidAnnotationParamException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedAnnotationOptionException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionExpectedDataTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionValueDataTypeException;
use phpDocumentor\Reflection\Types\ClassString;
use function is_bool;
use function is_string;

/**
 * @Annotation
 * @Annotation\Target({"PROPERTY"})
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ExcelColumn extends AbstractOptionsAnnotation
{
    /**
     * Fully qualified ExcelCell class
     *
     * @Annotation\Required()
     *
     * @var class-string<AbstractExcelCell>
     */
    private string $targetExcelCellClass;

    /**
     * Excel column key in A-Z notation or human-readable name from Excel header.
     * Can also be constant field id like A3, C10 etc.
     *
     * @Annotation\Required()
     */
    private string $columnKey;

    /**
     * Column name- default to $columnKey
     */
    private string $cellName;

    /**
     * Whether column cells are required or not
     */
    private bool $required;


    /**
     * @param array{
     *     targetExcelCellClass: class-string<AbstractExcelCell>,
     *     columnKey: string|null,
     *     cellName: string,
     *     required: bool,
     *     options: array,
     *     value: string|null,
     * }|string $data
     *
     * @param class-string<AbstractExcelCell>|null $targetExcelCellClass
     *
     * @throws InvalidAnnotationParamException
     * @throws UnexpectedAnnotationOptionException
     * @throws UnexpectedOptionValueDataTypeException
     * @throws UnexpectedOptionExpectedDataTypeException
     */
    public function __construct(
        string|array $data = [],
        ?string      $targetExcelCellClass = null,
        ?string      $columnKey = null,
        ?string      $cellName = null,
        ?bool        $required = null,
        ?array       $options = null,
    ) {
        parent::__construct(['options' => $options ?? $data['options'] ?? []]);
        $required = $required ?? $data['required'] ?? true;
        if (!is_bool($required)) {

            throw new InvalidAnnotationParamException('required', static::class, $required, 'bool');
        }
        $columnKey = null === $columnKey && is_string($data) ? $data : $columnKey;

        $this->targetExcelCellClass = $targetExcelCellClass ?? $data['targetExcelCellClass'];
        $this->columnKey = $columnKey ?? $data['columnKey'] ?? $data['value'] ?? '';
        $this->cellName = $cellName ?? $data['cellName'] ?? $this->columnKey;
        $this->required = $required;
    }

    protected function getSupportedOptions(): array
    {
        return [
            DateTimeExcelCell::OPTION_REVERSE_FORMAT => 'string',
        ];
    }

    public function getCellName(): string
    {
        return $this->cellName;
    }

    /**
     * @return class-string<AbstractExcelCell>
     */
    public function getTargetExcelCellClass(): string
    {
        return $this->targetExcelCellClass;
    }

    public function getColumnKey(): string
    {
        return $this->columnKey;
    }

    public function setColumnKey(string $columnKey): static
    {
        $this->columnKey = $columnKey;

        return $this;
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
        return $this->getOptions()[DateTimeExcelCell::OPTION_REVERSE_FORMAT] ?? null;
    }
}