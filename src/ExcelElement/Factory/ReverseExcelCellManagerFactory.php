<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\Factory;

use Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell\ReverseExcelCellManager;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;

class ReverseExcelCellManagerFactory
{
    /** @var ReverseExcelCellFactory */
    private $reverseExcelCellFactory;

    public function __construct(
        ReverseExcelCellFactory $reverseExcelCellFactory
    )
    {
        $this->reverseExcelCellFactory = $reverseExcelCellFactory;
    }

    public function createFromModelMetadata(ModelMetadata $modelMetadata): ReverseExcelCellManager
    {
        $reverseExcelCells = [];
        foreach ($modelMetadata->getModelPropertiesMetadata() as $propertyMetadata) {
            $reverseExcelCells[$propertyMetadata->getPropertyName()] =
                $this->reverseExcelCellFactory->resolveFromExcelCellClassAndExcelColumn(
                    $propertyMetadata->getExcelColumn()->getTargetExcelCellClass(),
                    $propertyMetadata->getExcelColumn()
                );
        }

        return (new ReverseExcelCellManager())
            ->setModelMetadata($modelMetadata)
            ->setPropertyReverseExcelCells($reverseExcelCells);
    }
}