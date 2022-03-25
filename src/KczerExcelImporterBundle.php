<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle;

use Kczer\ExcelImporterBundle\DependencyInjection\Compiler\ExcelCellRegistrationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class KczerExcelImporterBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ExcelCellRegistrationPass());
    }
}
