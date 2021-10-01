<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator\AbstractValidator;
use Symfony\Contracts\Translation\TranslatorInterface;
use function trim;

abstract class AbstractExcelCell
{
    /** @var TranslatorInterface */
    protected $translator;

    /** @var string|null */
    protected $errorMessage = null;

    /** @var string|null */
    protected $rawValue = null;

    /** @var string */
    protected $name;

    /** @var bool */
    protected $required;

    /** @var AbstractValidator[] */
    private $validators = [];

    /** @var bool */
    protected $validateObligatory = true;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getName(): string
    {
        return $this->translator->trans($this->name);
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function setRequired(bool $required): self
    {
        $this->required = $required;

        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return null !== $this->errorMessage ? $this->translator->trans($this->errorMessage) : null;
    }

    public function hasError(): bool
    {
        return null !== $this->errorMessage;
    }

    protected function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;

        return $this;
    }

    /**
     * @return AbstractValidator[]
     */
    public function getValidators(): array
    {
        return $this->validators;
    }

    /**
     * @param AbstractValidator[] $validators
     */
    public function setValidators(array $validators): self
    {
        $this->validators = $validators;
        return $this;
    }

    public function getRawValue(): ?string
    {
        return $this->rawValue;
    }

    public function getDisplayValue(): string
    {
        return (string)$this->rawValue;
    }

    /**
     * @return mixed|null
     */
    public function getValue()
    {
        return !$this->hasError() ? $this->getParsedValue() : null;
    }

    /**
     * Get value parsed to proper data type
     *
     * @return mixed|null
     */
    protected abstract function getParsedValue();

    /**
     * Check any cell-specific value requirements (Like database presence or format matching)
     *
     * @return string|null String message if any requirement was not met or null if all requirements are met
     */
    protected abstract function validateValueRequirements(): ?string;


    public function setRawValue(string $rawValue): self
    {
        $rawValue = trim($rawValue);
        $this->rawValue = '' !== $rawValue ? $rawValue : null;

        $this->setErrorMessage($this->validateObligatory ? $this->validateValueObligatory() : null);
        if (
            ($this->required && !$this->hasError()) ||
            (!$this->required && null !== $this->rawValue)
        ) {
            $this->setErrorMessage($this->validateValueWithValidators() ?? $this->validateValueRequirements());
        }

        return $this;
    }

    /**
     * @return string Message in format [cellName]- [errorMessage]
     */
    protected function createErrorMessageWithNamePrefix(string $errorMessage): string
    {
        return sprintf('%s- %s', $this->getName(), $this->translator->trans($errorMessage));
    }

    private function validateValueWithValidators(): ?string
    {
        foreach ($this->validators as $validator) {
            if (!$validator->isExcelCellValueValid($this->rawValue)) {
                [$message, $params] = $validator->getMessageWithParams();

                return $this->createErrorMessageWithNamePrefix($this->translator->trans($message, $params));
            }
        }

        return null;
    }

    private function validateValueObligatory(): ?string
    {
        if (null === $this->rawValue && $this->required) {

            return $this->createErrorMessageWithNamePrefix('excel_importer.validator.messages.value_required');
        }

        return null;
    }
}