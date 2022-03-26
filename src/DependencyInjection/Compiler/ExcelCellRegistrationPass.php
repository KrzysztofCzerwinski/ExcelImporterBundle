<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\DependencyInjection\Compiler;

use Kczer\ExcelImporterBundle\Maker\ModelMaker;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ExcelCellRegistrationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(ModelMaker::class)) {

            return;
        }

        $modelMakerDefinition = $container->getDefinition(ModelMaker::class);
        foreach ($container->findTaggedServiceIds('excel_importer.excel_cell') as $id => $tags) {
            if ($container->getDefinition($id)->isAbstract()) {

                continue;
            }
            $modelMakerDefinition->addMethodCall('addExcelCell', [new Reference($id)]);
        }
    }
}