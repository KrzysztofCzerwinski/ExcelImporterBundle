<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\Factory;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration\ExcelCellConfiguration;

class ExcelCellFactory
{
    /** @var array<class-string<AbstractExcelCell>, AbstractExcelCell> */
    private array $excelCells = [];

    /**
     * @noinspection PhpUnused method used by compiler pass
     */
    public function addExcelCell(AbstractExcelCell $excelCell): void
    {
        $this->excelCells[$excelCell::class] = $excelCell;
    }

    public function makeSkeletonFromConfiguration(
        ExcelCellConfiguration $configuration,
        array                  $options
    ): AbstractExcelCell {
        $excelCell = clone $this->excelCells[$configuration->getExcelCellClass()];

        return $excelCell
            ->setName($configuration->getCellName())
            ->setRequired($configuration->isCellRequired())
            ->setValidators($configuration->getValidators())
            ->setOptions($options)
        ;
    }
}
