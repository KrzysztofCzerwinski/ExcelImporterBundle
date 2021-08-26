<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\Factory;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration\ExcelCellConfiguration;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExcelCellFactory
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function makeSkeletonFromConfiguration(ExcelCellConfiguration $configuration): AbstractExcelCell
    {
        $excelCellClass = $configuration->getExcelCellClass();
        /** @var AbstractExcelCell $excelCell */
        $excelCell = $this->container->get($excelCellClass);

        return $excelCell->setName($configuration->getCellName())->setRequired($configuration->isCellRequired());
    }
}