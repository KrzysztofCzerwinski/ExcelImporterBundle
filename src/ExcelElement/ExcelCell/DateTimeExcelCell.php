<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\ExcelElement\ExcelCell;

use Kczer\ExcelImporterBundle\MessageInterface;
use DateTime;
use Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;

/**
 * An EXCEL cell that requires value to be string acceptable by DateTime constructor
 */
class DateTimeExcelCell extends AbstractExcelCell
{

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

            return $this->createErrorMessageWithNamePrefix(MessageInterface::DATETIME_STRING_VALUE_REQUIRED);
        }

        return null;
    }
}