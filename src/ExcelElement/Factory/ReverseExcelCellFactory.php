<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\Factory;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractDictionaryExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\BoolExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\DateTimeExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell\BoolReverseExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell\DateTimeReverseExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell\DictionaryReverseExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ReverseExcelCell\ReverseExcelCell;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function is_a;

class ReverseExcelCellFactory
{
    private const REVERSE_EXCEL_CELL_EXCEL_CELL_MAPPINGS = [
        DictionaryReverseExcelCell::class => AbstractDictionaryExcelCell::class,
        BoolReverseExcelCell::class => BoolExcelCell::class,
        DateTimeReverseExcelCell::class => DateTimeExcelCell::class,
    ];

    /** @var array<class-string<AbstractExcelCell>, AbstractExcelCell> */
    private array $excelCells = [];

    public function __construct(
        private ContainerInterface $container,
    ){
    }

    /**
     * @noinspection PhpUnused method used by compiler pass
     */
    public function addExcelCell(AbstractExcelCell $excelCell): void
    {
        $this->excelCells[$excelCell::class] = $excelCell;
    }

    public function resolveFromExcelCellClassAndExcelColumn(string $targetExcelCellClass, ExcelColumn $excelColumn): ReverseExcelCell
    {
        $targetReverseExcelCellClass = ReverseExcelCell::class;
        foreach (self::REVERSE_EXCEL_CELL_EXCEL_CELL_MAPPINGS as $reverseExcelCellClass => $excelCellClass) {
            if (!is_a($targetExcelCellClass, $excelCellClass, true)) {

                continue;
            }
            $targetReverseExcelCellClass = $reverseExcelCellClass;

            break;
        }
        /** @var ReverseExcelCell $reverseExcelCell */
        $reverseExcelCell = $this->container->get($targetReverseExcelCellClass);
        if ($reverseExcelCell instanceof DictionaryReverseExcelCell) {
            /** @var AbstractDictionaryExcelCell $dictionaryExcelCell */
            $dictionaryExcelCell = $this->excelCells[$targetExcelCellClass];
            $reverseExcelCell->setDictionary($dictionaryExcelCell->getDictionary());
        } elseif ($reverseExcelCell instanceof DateTimeReverseExcelCell) {
            $reverseExcelCell->setReversedFormat($excelColumn->getReverseReverseDateTimeFormat());
        }

        return $reverseExcelCell->setBaseExcelCellClass($targetExcelCellClass);
    }
}