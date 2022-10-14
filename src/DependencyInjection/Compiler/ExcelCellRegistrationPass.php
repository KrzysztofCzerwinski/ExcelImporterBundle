<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\DependencyInjection\Compiler;

use Kczer\ExcelImporterBundle\ExcelElement\Factory\ExcelCellFactory;
use Kczer\ExcelImporterBundle\ExcelElement\Factory\ReverseExcelCellFactory;
use Kczer\ExcelImporterBundle\Maker\ModelMaker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExcelCellRegistrationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (in_array(false, array_map([$container, 'has'], [
            ModelMaker::class,
            ExcelCellFactory::class,
            ReverseExcelCellFactory::class,
        ]))) {

            return;
        }

        $modelMakerDefinition = $container->getDefinition(ModelMaker::class);
        $excelCellFactoryDefinition = $container->getDefinition(ExcelCellFactory::class);
        $reverseExcelCellFactoryDefinition = $container->getDefinition(ReverseExcelCellFactory::class);
        foreach ($container->findTaggedServiceIds('excel_importer.excel_cell') as $id => $tags) {
            if ($container->getDefinition($id)->isAbstract()) {

                continue;
            }

            $modelMakerDefinition->addMethodCall('addExcelCell', [new Reference($id)]);
            $excelCellFactoryDefinition->addMethodCall('addExcelCell', [new Reference($id)]);
            $reverseExcelCellFactoryDefinition->addMethodCall('addExcelCell', [new Reference($id)]);
        }
    }
}
