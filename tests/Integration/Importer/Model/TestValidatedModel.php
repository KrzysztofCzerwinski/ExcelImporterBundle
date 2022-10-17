<?php
/** @noinspection PhpUnused */
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Integration\Importer\Model;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\Annotation\Validator;
use Kczer\ExcelImporterBundle\Annotation\Validator\UniqueModel;

#[UniqueModel('stringCell')]
class TestValidatedModel
{
    #[Validator\Regex('string\d+')]
    #[ExcelColumn('string cell')]
    private string $stringCell;

    #[Validator\Length(10)]
    #[ExcelColumn('optional string cell')]
    private ?string $optionalStringCell;

    #[ExcelColumn('int cell')]
    private int $intCell;

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
}
