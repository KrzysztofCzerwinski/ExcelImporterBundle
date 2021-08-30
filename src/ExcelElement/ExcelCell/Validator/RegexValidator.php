<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator;

use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidRegexExpressionException;
use function preg_match;
use function sprintf;
use function trim;

class RegexValidator extends AbstractValidator
{
    /**  @var string */
    private $pattern;


    /**
     * @throws InvalidRegexExpressionException
     */
    public function __construct(string $pattern, string $message)
    {
        parent::__construct($message);

        $this->pattern = $pattern;
        if (!$this->isRegexValid($this->getFullMatchRegex())) {

            throw new InvalidRegexExpressionException($this->pattern);
        }
    }

    public function isExcelCellValueValid(string $rawValue): bool
    {
        return 0 !== preg_match($this->getFullMatchRegex(), $rawValue);
    }

    protected function getReplaceableProperties(): array
    {
        return ['pattern' => $this->pattern];
    }

    private function getFullMatchRegex(): string
    {
        return sprintf(
            '%s%s%s',
            '/^',
            trim($this->pattern, '/ '),
            '$/i'
        );
    }

    private function isRegexValid(string $regex): bool
    {
        return false !== @preg_match($regex, '');
    }
}