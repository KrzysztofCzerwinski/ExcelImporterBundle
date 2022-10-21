<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests;

use Kczer\ExcelImporterBundle\KczerExcelImporterBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;

class TestKernel extends Kernel
{
    /**
     * @inheritDoc
     */
    public function registerBundles(): array
    {
        return [
            new KczerExcelImporterBundle(),
            new FrameworkBundle(),
        ];
    }

    /**
     * @inheritDoc
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
    }
}
