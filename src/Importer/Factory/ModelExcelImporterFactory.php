<?php
declare(strict_types=1);


namespace Kczer\ExcelImporterBundle\Importer\Factory;

use Kczer\ExcelImporterBundle\Importer\ModelExcelImporter;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ModelExcelImporterFactory
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function createModelExcelImporter(string $modelClass): ModelExcelImporter
    {
        /** @var ModelExcelImporter $modelExcelImporter*/
        $modelExcelImporter = $this->container->get(ModelExcelImporter::class);

        return $modelExcelImporter->setImportModelClass($modelClass);
    }
}