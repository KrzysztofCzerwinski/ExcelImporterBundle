<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use DateTime;
use Exception;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * An EXCEL cell that requires value to be string acceptable by DateTime constructor
 */
class DateTimeExcelCell extends AbstractExcelCell
{
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct($translator);
    }

    /**
     * @inheritDoc
     *
     * @throws Exception
     */
    protected function getParsedValue(): ?DateTime
    {
        return null !== $this->rawValue ? new DateTime($this->rawValue) : null;
    }


    /**
     * @inheritDoc
     */
    protected function validateValueRequirements(): ?string
    {
        try {
            new DateTime($this->rawValue);
        } catch (Exception $exception) {

            return $this->createErrorMessageWithNamePrefix('excel_importer.validator.messages.datetime_string_value_required');
        }

        return null;
    }
}