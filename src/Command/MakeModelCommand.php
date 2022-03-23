<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MakeModelCommand extends Command
{
    /** @var TranslatorInterface */
    private $translator;

    public function __construct(
        TranslatorInterface $translator
    ) {
        $this->translator = $translator;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('excel_importer:make:model')
            ->setDescription($this->translator->trans('excel_importer.command.make_model.description'))
        ;
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return 0;
    }
}