<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Integration\Importer\Model;

use DateTime;
use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;

class TestTechnicalModel
{
    #[ExcelColumn]
    private string $stringCell;

    #[ExcelColumn]
    private ?string $optionalStringCell;

    #[ExcelColumn]
    private int $intCell;

    #[ExcelColumn]
    private float $floatCell;

    #[ExcelColumn]
    private DateTime $dateTimeExcelCell;

    #[ExcelColumn]
    private bool $boolExcelCell;

    public function getStringCell(): string
    {
        return $this->stringCell;
    }

    public function setStringCell(string $stringCell): static
    {
        $this->stringCell = $stringCell;

        return $this;
    }

    public function getOptionalStringCell(): ?string
    {
        return $this->optionalStringCell;
    }

    public function setOptionalStringCell(?string $optionalStringCell): static
    {
        $this->optionalStringCell = $optionalStringCell;

        return $this;
    }

    public function getIntCell(): int
    {
        return $this->intCell;
    }

    public function setIntCell(int $intCell): static
    {
        $this->intCell = $intCell;

        return $this;
    }

    public function getFloatCell(): float
    {
        return $this->floatCell;
    }

    public function setFloatCell(float $floatCell): static
    {
        $this->floatCell = $floatCell;

        return $this;
    }

    public function getDateTimeExcelCell(): DateTime
    {
        return $this->dateTimeExcelCell;
    }

    public function setDateTimeExcelCell(DateTime $dateTimeExcelCell): static
    {
        $this->dateTimeExcelCell = $dateTimeExcelCell;

        return $this;
    }

    public function isBoolExcelCell(): bool
    {
        return $this->boolExcelCell;
    }

    public function setBoolExcelCell(bool $boolExcelCell): static
    {
        $this->boolExcelCell = $boolExcelCell;

        return $this;
    }
}
