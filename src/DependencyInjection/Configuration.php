<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\DependencyInjection;

use DateTime;
use InvalidArgumentException;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\BoolExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\DateTimeExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\FloatExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\IntegerExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\StringExcelCell;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use function is_subclass_of;
use function sprintf;
use function trim;

class Configuration implements ConfigurationInterface
{
    private const DEFAULT_TRUE_VALUES = ['tak', 'y', 'yes', 't', 't', 'true', '1'];

    private const DEFAULT_FALSE_VALUES = ['nie', 'n', 'no', 'false', 'f', '0'];

    private const TYPES_MAPPINGS = [
        'string' => StringExcelCell::class,
        'int' => IntegerExcelCell::class,
        'float' => FloatExcelCell::class,
        'bool' => BoolExcelCell::class,
        DateTime::class => DateTimeExcelCell::class,
    ];

    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('kczer_excel_importer');

        /** @formatter:off */
        $treeBuilder
            ->getRootNode()
                ->children()
                    ->arrayNode('excel_cell')
                    ->addDefaultsIfNotSet()
                        ->children()
                            ->append($this->getBoolConfigurationNode())
                            ->append($this->getTypesConfigurationNode())
                        ->end()
                    ->end()
                ->end()
        ;
        /** @formatter:on */

        return $treeBuilder;
    }

    public function getBoolConfigurationNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('bool');

        /** @formatter:off */
        return $treeBuilder
            ->getRootNode()
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('true_values')
                        ->defaultValue(self::DEFAULT_TRUE_VALUES)
                        ->scalarPrototype()
                        ->end()
                    ->end()
                    ->arrayNode('false_values')
                        ->defaultValue(self::DEFAULT_FALSE_VALUES)
                        ->scalarPrototype()
                        ->end()
                    ->end()
                    ->booleanNode('empty_as_false')
                        ->defaultValue(true)
                    ->end()
                ->end()
        ;
        /** @formatter:on */
    }

    public function getTypesConfigurationNode(): NodeDefinition
    {
        $treeBuilder = new TreeBuilder('types');

        /** @formatter:off */
        return $treeBuilder
            ->getRootNode()
                ->useAttributeAsKey('name')
                ->defaultValue(self::TYPES_MAPPINGS)
                ->validate()
                    ->always(static function (array $typeMappings): array {
                        $trimmedTypeMappings = [];
                        foreach ($typeMappings as $type => $excelCellClass) {
                            $trimmedTypeMappings[trim($type, '\\ ')] = trim($excelCellClass, '\\ ');
                            if (is_subclass_of($excelCellClass, AbstractExcelCell::class)) {

                                continue;
                            }

                            throw new InvalidArgumentException(sprintf(
                                'type mappings must contain valid class names extending "%s" class. "%s" given',
                                AbstractExcelCell::class,
                                $excelCellClass
                            ));
                        }

                        return $trimmedTypeMappings + self::TYPES_MAPPINGS;
                    })
                ->end()
                ->scalarPrototype()
                ->end()
        ;
        /** @formatter:on */
    }
}