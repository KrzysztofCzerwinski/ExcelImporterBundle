<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

abstract class AbstractErrorAwareModel
{
    /** @var bool */
    private $valid;

    /** @var string */
    private $mergedAllErrorMessages;

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): self
    {
        $this->valid = $valid;

        return $this;
    }

    public function getMergedAllErrorMessages(): string
    {
        return $this->mergedAllErrorMessages;
    }

    public function setMergedAllErrorMessages(string $mergedAllErrorMessages): self
    {
        $this->mergedAllErrorMessages = $mergedAllErrorMessages;

        return $this;
    }
}