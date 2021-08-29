<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator;

use Kczer\ExcelImporterBundle\Exception\MissingValidatorOptionsException;
use Kczer\ExcelImporterBundle\MessageInterface;
use function strlen;

class LengthAbstractValidator extends AbstractValidator
{
    /**
     * @var int
     */
    private $minLength;

    /**
     * @Required
     *
     * @var int
     */
    private $maxLength;

    /**
     * @param array{minLength: int, maxLength: int, errorMessage: string} $options
     *
     * @throws MissingValidatorOptionsException
     */
    public function __construct(array $options)
    {
        parent::__construct($options + ['message' =>  MessageInterface::LENGTH_VALIDATOR_DEFAULT_MESSAGE]);

        $minLength = $options['minLength'] ?? 0;
        $this->minLength = $minLength;
        $this->maxLength = $options['maxLength'];

        $this->options += compact('minLength');
    }

    protected function getRequiredOptionNames(): array
    {
        return ['maxLength'];
    }

    public function isExcelCellValueValid(string $rawValue): bool
    {
        $valueLength = strlen($rawValue);

        return $valueLength >= $this->minLength && $valueLength <= $this->maxLength;
    }

}