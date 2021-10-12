<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private const DEFAULT_TRUE_VALUES = ['tak', 'y', 'yes', 't', 't', 'true', '1'];

    private const DEFAULT_FALSE_VALUES = ['nie', 'n', 'no', 'false', 'f', '0'];

    /**
     * @inheritDoc
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('kczer_excel_importer');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('excel_cell')
                ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('bool')
                            ->addDefaultsIfNotSet()
                            ->children()
                                ->arrayNode('true_values')->scalarPrototype()->end()
                                    ->defaultValue(self::DEFAULT_TRUE_VALUES)->end()
                                ->arrayNode('false_values')->scalarPrototype()->end()
                                    ->defaultValue(self::DEFAULT_FALSE_VALUES)->end()
                                ->booleanNode('empty_as_false')->defaultValue(true)->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}