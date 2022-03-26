<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\DependencyInjection;

use Exception;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\BoolExcelCell;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\Yaml\Yaml;

class KczerExcelImporterExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configDir = new FileLocator(__DIR__ . '/../Resources/config');
        $loader = new YamlFileLoader($container, $configDir);
        $loader->load('services.yaml');

        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('excel_cell.bool.true_values', $config['excel_cell']['bool']['true_values']);
        $container->setParameter('excel_cell.bool.false_values', $config['excel_cell']['bool']['false_values']);
        $container->setParameter('excel_cell.bool.empty_as_false', $config['excel_cell']['bool']['empty_as_false']);

        $container->registerForAutoconfiguration(AbstractExcelCell::class)->addTag('excel_importer.excel_cell');
    }

    public function prepend(ContainerBuilder $container)
    {
        foreach (Yaml::parseFile(__DIR__ . '/../Resources/config/packages/translation.yaml') as $configKey => $configValue) {
            $container->prependExtensionConfig($configKey, $configValue);
        }
    }
}