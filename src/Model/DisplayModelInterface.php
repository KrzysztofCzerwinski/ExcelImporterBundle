<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Model;

interface DisplayModelInterface
{
    public function setAllMergedErrorMessages(string $mergedAllErrorMessages): self;

    public function getAllMergedErrorMessages(): string;
}