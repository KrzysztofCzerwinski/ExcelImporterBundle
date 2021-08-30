<?php
declare(strict_types=1);


namespace Kczer\ExcelImporterBundle\Importer\Factory;

use Kczer\ExcelImporterBundle\Exception\UnexpectedDisplayModelClassException;
use Kczer\ExcelImporterBundle\Importer\ModelExcelImporter;
use Kczer\ExcelImporterBundle\Model\DisplayModelInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function is_subclass_of;

class ModelExcelImporterFactory
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @throws UnexpectedDisplayModelClassException
     */
    public function createModelExcelImporter(string $modelClass, ?string $displayModelClass = null): ModelExcelImporter
    {
        /** @var ModelExcelImporter $modelExcelImporter*/
        $modelExcelImporter = $this->container->get(ModelExcelImporter::class);

        if (null !== $displayModelClass && !is_subclass_of($displayModelClass, DisplayModelInterface::class, true)) {

            throw new UnexpectedDisplayModelClassException($modelClass);
        }

        return $modelExcelImporter->setImportModelClass($modelClass)->setDisplayModelClass($displayModelClass);
    }
}