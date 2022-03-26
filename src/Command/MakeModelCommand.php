<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Command;

use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidAnnotationParamException;
use Kczer\ExcelImporterBundle\Exception\Annotation\InvalidDisplayModelSetterParameterTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\ModelPropertyNotSettableException;
use Kczer\ExcelImporterBundle\Exception\Annotation\NotExistingModelClassException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedAnnotationOptionException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedColumnExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionExpectedDataTypeException;
use Kczer\ExcelImporterBundle\Exception\Annotation\UnexpectedOptionValueDataTypeException;
use Kczer\ExcelImporterBundle\Exception\DuplicateExcelIdentifierException;
use Kczer\ExcelImporterBundle\Maker\ModelMaker;
use PhpOffice\PhpSpreadsheet\Exception;
use ReflectionException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

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
     * @throws DuplicateExcelIdentifierException
     * @throws InvalidAnnotationParamException
     * @throws UnexpectedAnnotationOptionException
     * @throws UnexpectedOptionExpectedDataTypeException
     * @throws UnexpectedOptionValueDataTypeException
     * @throws InvalidDisplayModelSetterParameterTypeException
     * @throws ModelPropertyNotSettableException
     * @throws NotExistingModelClassException
     * @throws UnexpectedColumnExcelCellClassException
     * @throws Exception
     * @throws ReflectionException
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
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