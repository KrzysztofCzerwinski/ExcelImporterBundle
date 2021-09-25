<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use Symfony\Contracts\Translation\TranslatorInterface;
use function in_array;
use function strtolower;

/**
 * An EXCEL cell that treats strings defined in SUPPORTED_TRUE_VALUES as true, an others as false
 */
class BoolExcelCell extends AbstractExcelCell
{
    /** @var string[] */
    private const SUPPORTED_TRUE_VALUES  = ['y', 'yes', 't', 'tak', 't', 'true'];


    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator);
    }


    /**
     * @inheritDoc
     */
    protected function getParsedValue(): ?bool
    {
        return null !== $this->rawValue ? in_array(strtolower($this->rawValue), self::SUPPORTED_TRUE_VALUES, true) : null;
    }

    /**
     * @inheritDoc
     */
    protected function validateValueRequirements(): ?string
    {
        return null;
    }
}