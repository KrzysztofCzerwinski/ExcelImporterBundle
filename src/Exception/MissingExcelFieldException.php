<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Exception;
use Throwable;

class MissingExcelFieldException extends Exception
{
    /** @var string */
    private $fieldId;

    public function __construct(string $fieldId, Throwable $previous = null)
    {
        $this->fieldId = $fieldId;

        parent::__construct("Field '$this->fieldId' not found in EXCEL file", 0, $previous);
    }

    public function getFieldId(): string
    {
        return $this->fieldId;
    }
}