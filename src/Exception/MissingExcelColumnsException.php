<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exception;

use Exception;
use Throwable;
use function implode;
use function sprintf;


class MissingExcelColumnsException extends Exception
{
    /** @var string[] */
    private $missingColumnKeys;

    /**
     * @param string[] $missingColumnKeys
     */
    public function __construct(array $missingColumnKeys, Throwable $previous = null)
    {
        $this->missingColumnKeys = $missingColumnKeys;

        parent::__construct(
            sprintf('Missing column keys in EXCEL file: %s', implode(', ', $this->missingColumnKeys)),
            0,
            $previous
        );
    }

    /**
     * @return string[]
     */
    public function getMissingColumnKeys(): array
    {
        return $this->missingColumnKeys;
    }
}