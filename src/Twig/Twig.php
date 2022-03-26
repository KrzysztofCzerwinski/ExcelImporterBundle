<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Twig;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Twig extends Environment
{
    public function __construct() {
        $loader = new FilesystemLoader('../Resources/templates', __DIR__);

        parent::__construct($loader);

        $this->addExtension(new ClassHelperExtension());
    }
}