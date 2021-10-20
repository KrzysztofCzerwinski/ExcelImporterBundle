<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

class ExcelRowsMetadata
{
    /**
     * @var int|null First index containing data (0-indexed)
     */
    private $firstDataRowIndex;

    /**
     * @var int|null Last index containing data (0-indexed)
     */
    private $lastDataRowIndex;

    /** @var string[]|null */
    private $columnKeyMappings;

    public function getFirstDataRowIndex(): ?int
    {
        return $this->firstDataRowIndex;
    }

    public function setFirstDataRowIndex(?int $firstDataRowIndex): self
    {
        $this->firstDataRowIndex = $firstDataRowIndex;
        return $this;
    }

    public function getLastDataRowIndex(): ?int
    {
        return $this->lastDataRowIndex;
    }

    public function setLastDataRowIndex(?int $lastDataRowIndex): self
    {
        $this->lastDataRowIndex = $lastDataRowIndex;
        return $this;
    }

    /**
     * @return string[]|null
     */
    public function getColumnKeyMappings(): ?array
    {
        return $this->columnKeyMappings;
    }

    /**
     * @param string[]|null $columnKeyMappings
     */
    public function setColumnKeyMappings(?array $columnKeyMappings): self
    {
        $this->columnKeyMappings = $columnKeyMappings;
        return $this;
    }
}