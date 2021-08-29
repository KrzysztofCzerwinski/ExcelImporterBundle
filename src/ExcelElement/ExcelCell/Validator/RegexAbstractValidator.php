<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Validator;

use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidRegexExpressionException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Exception\MissingValidatorOptionsException;
use Kczer\ExcelImporterBundle\MessageInterface;
use function preg_match;
use function sprintf;
use function trim;

class RegexAbstractValidator extends AbstractValidator
{
    /**  @var string */
    private $pattern;

    public static function isRegexValid(string $regex): bool
    {
        return false !== @preg_match($regex, '');
    }

    /**
     * @param array{pattern: string, errorMessage : string} $options
     *
     * @throws ExcelImportConfigurationException
     */
    public function __construct(array $options)
    {
        parent::__construct($options + ['message' =>  MessageInterface::REGEX_VALIDATOR_DEFAULT_MESSAGE]);

        $pattern = $options['pattern'];
        if (!self::isRegexValid($pattern)) {

            throw new InvalidRegexExpressionException($pattern);
        }
        $this->pattern = $pattern;
    }

    protected function getRequiredOptionNames(): array
    {
        return ['pattern'];
    }

    public function isExcelCellValueValid(string $rawValue): bool
    {
        return 0 !== preg_match($this->getFullMatchRegex(), $rawValue);
    }

    private function getFullMatchRegex(): string
    {
        return sprintf(
            '%s%s%s',
            '/^',
            trim($this->pattern, '/ '),
            '$/'
        );
    }
}