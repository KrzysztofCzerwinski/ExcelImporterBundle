<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\DependencyInjection;

use Exception;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;

class ExcelImporterExtension extends Extension
{
    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configDir = new FileLocator(__DIR__ . '/../../config');
        $loader = new YamlFileLoader($container, $configDir);
        $loader->load('services.yaml');
        $this->processConfiguration(new ConfigSchema(), $configs);
        var_dump($container->getAliases());
    }
}