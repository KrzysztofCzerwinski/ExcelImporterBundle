<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Command;

use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidAnnotationParamException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedAnnotationOptionException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionExpectedDataTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionValueDataTypeException;
use Kczer\ExcelImporterBundle\Exception\DuplicateExcelIdentifierException;
use Kczer\ExcelImporterBundle\Maker\ModelMaker;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MakeModelCommand extends Command
{
    /** @var TranslatorInterface */
    private $translator;

    /** @var ModelMaker */
    private $modelMaker;

    public function __construct(
        TranslatorInterface $translator,
        ModelMaker          $modelMaker
    ) {
        $this->translator = $translator;

        parent::__construct();
        $this->modelMaker = $modelMaker;
    }

    protected function configure()
    {
        $this
            ->setName('excel_importer:make:model')
            ->setDescription($this->translator->trans('excel_importer.command.make_model.description'))
        ;
    }

    /**
     * @throws UnexpectedAnnotationOptionException
     * @throws UnexpectedOptionValueDataTypeException
     * @throws UnexpectedOptionExpectedDataTypeException
     * @throws DuplicateExcelIdentifierException
     * @throws InvalidAnnotationParamException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->modelMaker
            ->setCommandInOut($input, $output)
            ->makeModelClasses()
        ;

        return 0;
    }
}