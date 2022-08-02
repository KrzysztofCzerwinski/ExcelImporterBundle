<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Importer\Factory;

use Kczer\ExcelImporterBundle\Exception\UnexpectedDisplayModelClassException;
use Kczer\ExcelImporterBundle\Importer\ModelExcelImporter;
use Kczer\ExcelImporterBundle\Model\AbstractDisplayModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use function is_a;

class ModelExcelImporterFactory
{
    public function __construct(
        private ContainerInterface $container
    ) {
    }

    /**
     * @template TM
     * @template TD
     *
     * @param class-string<TM> $modelClass
     * @param class-string<TD>|null $displayModelClass
     *
     * @return ModelExcelImporter<TM, TD>
     *
     * @throws UnexpectedDisplayModelClassException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function createModelExcelImporter(string $modelClass, ?string $displayModelClass = null): ModelExcelImporter
    {
        /** @var ModelExcelImporter $modelExcelImporter */
        $modelExcelImporter = $this->container->get(ModelExcelImporter::class);

        if (null !== $displayModelClass && !is_a($displayModelClass, AbstractDisplayModel::class, true)) {

            throw new UnexpectedDisplayModelClassException($modelClass);
        }

        return $modelExcelImporter->setImportModelClass($modelClass)->setDisplayModelClass($displayModelClass);
    }
}