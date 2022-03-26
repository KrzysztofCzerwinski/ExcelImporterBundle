<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use function count;
use function end;
use function explode;
use function key;

class ClassHelperExtension extends AbstractExtension
{
    /**
     * @inheritDoc
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('short_name', [$this, 'getShortClassName']),
            new TwigFilter('namespace', [$this, 'getNamespace']),
            new TwigFilter('param', [$this, 'getParamString']),
            new TwigFilter('var_annotation', [$this, 'getVarAnnotationString']),
            new TwigFilter('return_type', [$this, 'getReturnTypeString']),
        ];
    }

    public function getShortClassName(string $className): string
    {
        $classNameParts = explode('\\', $className);

        return end($classNameParts);
    }

    public function getNamespace(string $className): string
    {
        $classNameParts = explode('\\', $className);
        if (1 === count($classNameParts)) {

            return '';
        }
        end($classNameParts);
        unset($classNameParts[key($classNameParts)]);

        return implode('\\', $classNameParts);
    }

    public function getParamString(string $paramTypeName, bool $isNullable, bool $ignoreEmptyBool = true): string
    {
        return $this->resolveParamObligatory($paramTypeName, $isNullable, $ignoreEmptyBool) ? "$paramTypeName " : "?$paramTypeName ";
    }

    public function getVarAnnotationString(string $paramTypeName, bool $isNullable, bool $ignoreEmptyBool = true): string
    {
        return "@var $paramTypeName" . (!$this->resolveParamObligatory($paramTypeName, $isNullable, $ignoreEmptyBool) ? '|null' : '');
    }

    public function getReturnTypeString(string $returnTypeName, bool $isNullable, bool $ignoreEmptyBool = true): string
    {
        return $this->resolveParamObligatory($returnTypeName, $isNullable, $ignoreEmptyBool) ? ": $returnTypeName" : ": ?$returnTypeName";
    }

    private function resolveParamObligatory(string $paramTypeName, bool $isNullable, bool $ignoreEmptyBool): bool
    {
        return !$isNullable || ('bool' === $paramTypeName && $ignoreEmptyBool);
    }
}