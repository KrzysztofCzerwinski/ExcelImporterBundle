<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Tests\Integration\Importer;

use DateTime;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\FileLoadException;
use Kczer\ExcelImporterBundle\Exception\InvalidNamedColumnKeyException;
use Kczer\ExcelImporterBundle\Exception\JsonExcelRowsLoadException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelColumnsException;
use Kczer\ExcelImporterBundle\Exception\MissingExcelFieldException;
use Kczer\ExcelImporterBundle\Exception\UnexpectedDisplayModelClassException;
use Kczer\ExcelImporterBundle\Importer\Factory\ModelExcelImporterFactory;
use Kczer\ExcelImporterBundle\Importer\Validator\UniqueModelValidator;
use Kczer\ExcelImporterBundle\Tests\Integration\Importer\Model\TestNamedModel;
use Kczer\ExcelImporterBundle\Tests\Integration\Importer\Model\TestTechnicalModel;
use Kczer\ExcelImporterBundle\Tests\Integration\Importer\Model\TestValidatedModel;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function file_get_contents;

class ModelExcelImporterTest extends KernelTestCase
{
    private ModelExcelImporterFactory $modelExcelImporterFactory;

    protected function setUp(): void
    {
        static::bootKernel();

        $this->modelExcelImporterFactory = static::$container->get(ModelExcelImporterFactory::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws UnexpectedDisplayModelClassException
     * @throws UnexpectedExcelCellClassException
     * @throws FileLoadException
     * @throws InvalidNamedColumnKeyException
     * @throws MissingExcelColumnsException
     * @throws MissingExcelFieldException
     */
    public function testCreatesModelsFromValidExcelWithTechnicalColumnKeys(): void
    {
        $modelExcelImporter = $this->modelExcelImporterFactory->createModelExcelImporter(TestTechnicalModel::class);

        $models = $modelExcelImporter->parseExcelFile(
            __DIR__ . '/resources/valid.xlsx',
            namedColumnKeys: false
        )->getModels();

        $this->assertEquals($this->createExpectedTestTechnicalModels(), $models);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidNamedColumnKeyException
     * @throws ContainerExceptionInterface
     * @throws UnexpectedExcelCellClassException
     * @throws MissingExcelFieldException
     * @throws FileLoadException
     * @throws MissingExcelColumnsException
     * @throws UnexpectedDisplayModelClassException
     */
    public function testCreatesModelsFromValidExcelWithNamedColumnKeys(): void
    {
        $modelExcelImporter = $this->modelExcelImporterFactory->createModelExcelImporter(TestNamedModel::class);

        $models = $modelExcelImporter->parseExcelFile(__DIR__ . '/resources/valid.xlsx')->getModels();

        $this->assertEquals($this->createExpectedTestNamedModels(), $models);
    }

    /**
     * @throws InvalidNamedColumnKeyException
     * @throws MissingExcelFieldException
     * @throws FileLoadException
     * @throws UnexpectedExcelCellClassException
     * @throws UnexpectedDisplayModelClassException
     * @throws MissingExcelColumnsException
     */
    public function testValidatesImportAndCells(): void
    {
        $modelExcelImporter = $this->modelExcelImporterFactory->createModelExcelImporter(TestValidatedModel::class);

        $modelExcelImporter->parseExcelFile(__DIR__ . '/resources/invalid.xlsx');
        $excelRows = $modelExcelImporter->getExcelRows();

        $this->assertArrayHasKey(UniqueModelValidator::class, $modelExcelImporter->getImportRelateErrorMessages());
        $this->assertEquals(
            'string cell- Wartość powinna spełniać wyrażenie regularne "string\d+" | optional string cell- Maksymalna dlugość wartości to 10',
            ($excelRows[3] ?? null)?->getMergedAllErrorMessages()
        );
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidNamedColumnKeyException
     * @throws ContainerExceptionInterface
     * @throws UnexpectedExcelCellClassException
     * @throws MissingExcelFieldException
     * @throws FileLoadException
     * @throws UnexpectedDisplayModelClassException
     * @throws MissingExcelColumnsException
     */
    public function testCreatesProperJsonFromValidExcel(): void
    {
        $modelExcelImporter = $this->modelExcelImporterFactory->createModelExcelImporter(TestNamedModel::class);

        $json = $modelExcelImporter->parseExcelFile(__DIR__ . '/resources/valid.xlsx')->getExcelRowsAsJson();

        $this->assertJsonStringEqualsJsonFile(__DIR__ . '/resources/valid.json', $json);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidNamedColumnKeyException
     * @throws ContainerExceptionInterface
     * @throws UnexpectedExcelCellClassException
     * @throws JsonExcelRowsLoadException
     * @throws MissingExcelFieldException
     * @throws MissingExcelColumnsException
     * @throws UnexpectedDisplayModelClassException
     */
    public function testCreatesModelsFromValidJsonWithTechnicalColumnKeys(): void
    {
        $modelExcelImporter = $this->modelExcelImporterFactory->createModelExcelImporter(TestTechnicalModel::class);

        $models = $modelExcelImporter->parseJson(
            file_get_contents(__DIR__ . '/resources/valid.json'),
            namedColumnKeys: false
        )->getModels();

        $this->assertEquals($this->createExpectedTestTechnicalModels(), $models);
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws InvalidNamedColumnKeyException
     * @throws ContainerExceptionInterface
     * @throws UnexpectedExcelCellClassException
     * @throws JsonExcelRowsLoadException
     * @throws MissingExcelFieldException
     * @throws MissingExcelColumnsException
     * @throws UnexpectedDisplayModelClassException
     */
    public function testCreatesModelsFromValidJsonWithNamedColumnKeys(): void
    {
        $modelExcelImporter = $this->modelExcelImporterFactory->createModelExcelImporter(TestNamedModel::class);

        $models = $modelExcelImporter->parseJson(file_get_contents(__DIR__ . '/resources/valid.json'))->getModels();

        $this->assertEquals($this->createExpectedTestNamedModels(), $models);
    }


    /**
     * @return array{2: TestTechnicalModel, 3: TestTechnicalModel}
     */
    private function createExpectedTestTechnicalModels(): array
    {
        return [
            2 => (new TestTechnicalModel())
                ->setStringCell('string1')
                ->setOptionalStringCell(null)
                ->setIntCell(1)
                ->setFloatCell(123.123)
                ->setDateTimeExcelCell(new DateTime('01.01.2022'))
                ->setBoolExcelCell(true),
            3 => (new TestTechnicalModel())
                ->setStringCell('string2')
                ->setOptionalStringCell('optionalString2')
                ->setIntCell(2)
                ->setFloatCell(123.124)
                ->setDateTimeExcelCell(new DateTime('02.01.2022'))
                ->setBoolExcelCell(false),
        ];
    }

    /**
     * @return array{2: TestNamedModel, 3: TestNamedModel}
     */
    private function createExpectedTestNamedModels(): array
    {
        return [
            2 => (new TestNamedModel())
                ->setStringCell('string1')
                ->setOptionalStringCell(null)
                ->setIntCell(1)
                ->setFloatCell(123.123)
                ->setDateTimeExcelCell(new DateTime('01.01.2022'))
                ->setBoolExcelCell(true),
            3 => (new TestNamedModel())
                ->setStringCell('string2')
                ->setOptionalStringCell('optionalString2')
                ->setIntCell(2)
                ->setFloatCell(123.124)
                ->setDateTimeExcelCell(new DateTime('02.01.2022'))
                ->setBoolExcelCell(false),
        ];
    }
}
