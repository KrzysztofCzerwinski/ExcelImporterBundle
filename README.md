### Table of contents:
- [Excel-importer](#excel-importer)
    * [Installation](#installation)
    * [Documentation](#documentation)
        + [Mapping EXCEL data to objects](#sample-import)
        + [Predefined ExcelCell classes](#predefined-excelcell-classes)
        + [DictionaryExcelCell](#dictionaryexcelcell)
        + [Custom ExcelCellClasses](#custom-excelcellclasses)
        + [More complex imports](#more-complex-imports)
        + [Data encoding](#data-encoding)

# ExcelImporterBundle

Excel-importer is a PHP library that enables to easy import EXCEL formats data and event parse it to objects.

## Installation
You can install it with composer like so:

```bash
composer require kczer/excel-importer-bundle
```


## Documentation

### Sample Import:

```php
<?php
//TestModel.php

class TestModel
{
    /**
     * @ExcelColumn(columnKey="A", cellName="user", targetExcelCellClass=TestDictionaryExcelCell::class)
     *
     * @var User
     */
    private $user;

    /**
     * @ExcelColumn(columnKey="B", cellName="name", targetExcelCellClass=StringExcelCell::class)
     *
     * @var string
     */
    private $name;


    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): TestModel
    {
        $this->user = $user;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }
}
```

```php
<?php
declare(strict_types=1);
//TestDictionaryExcelCell.php

class TestDictionaryExcelCell extends AbstractDictionaryExcelCell
{
    /** @var UserRepository */
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * @throws QueryException
     */
    protected function getDictionary(): array
    {
        return $this->userRepository->findAllIndexedById();
    }
}
```

```php
<?php
//ImportService.php
namespace App\Service;

use Kczer\ExcelImporterBundle\Exception\EmptyExcelColumnException;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;
use Kczer\ExcelImporterBundle\Exception\ExcelFileLoadException;
use Kczer\ExcelImporterBundle\Importer\Factory\ModelExcelImporterFactory;
use Kczer\ExcelImporterBundle\Importer\ModelExcelImporter;

class ImportService
{
    /** @var ModelExcelImporter */
    private $modelExcelImporter;

    public function __construct(ModelExcelImporterFactory $modelExcelImporterFactory) {
        $this->modelExcelImporter = $modelExcelImporterFactory->createModelExcelImporter(TestModel::class);
    }

    /**
     * @throws UnexpectedExcelCellClassException
     * @throws EmptyExcelColumnException
     * @throws ExcelFileLoadException
     */
    public function someMethod(string $filePath): void
    {
        $this->modelExcelImporter->parseExcelFile($filePath);
    }
}
```

### Predefined ExcelCell classes:

- **StringExcelCell** - Simple string values with no validation of data. getValue() returns string.
- **IntegerExcelCell** - Accepts only valid ints. getValue() returns int.
- **FloatExcelCell** - Accepts only valid numbers. getValue() return float.
- **BoolExcelCell** - Accepts 'y', 'yes', 't', 'tak', 't', 'true' (case insensitive) as true. Other values are considered false. getValue() returns bool.
- **DateTimeExcelCell** - Accepts all strings acceptable by DateTime class constructor. getValue() returns DataTime object
- **AbstractDictionaryExcelCell** - Abstract class useful for key-value types example below
- **AbstractMultipleDictionaryExcelCell** - Abstract class that can merge a couple of AbstractDictionaryCell dictionaries into one

### Mapping EXCEL data to objects
In previous example import resulted in array of ExcelRow objects, but if we wanted map out EXCEL data to some model object?
Let's assume that we have some PHP object named SomeModel. We can import it by extending **Kczer\ExcelImporter\AbstractModelExcelImporter**:

```php
<?php

namespace My\SampleNamespace;

use Kczer\ExcelImporterBundle\AbstractModelExcelImporter;

class SomeModelImporter extends AbstractModelExcelImporter
{

    /**
     * @inheritDoc
     */
    public function processParsedData(): void
    {
        // Gets array of SomeModel objects
        // Note that models are created ONLY if $this->hasErrors() return false
        $this->getModels(); 
    }

    /**
     * @inheritDoc
     */
    protected function getImportModelClass(): string
    {
        return SomeModel::class;// Our model class 
    }
}
```

It's almost ready. To make it work we need to do one more step of setup.
In our SomeModel class:

### DictionaryExcelCell

Dictionary EXCEL cells are used to define "range" of values, that cell can have.
It's perfect when cell value can be for example id of some resource from database.
Sample DictionaryExcelCell class:

```php
<?php

class SampleDictionaryExcelCell extends AbstractDictionaryExcelCell
{
    /** @var MyRepository */
    private $myRepository;
    
    public function __construct(MyRepository $myRepository)
    {
        $this->myRepository = $myRepository;
    }

    /**
     * @inheritDoc
     */
    protected function getDictionary(): array
    {
       return $myRepository->findIndexedBySomeUniqeCode();
    }
}
```

Now, we could just add this class to Import configuration of to **ExcelColumn** annotation, and excel-importer will accept only values from range 1-4 and getValue will return User objects.

### Custom ExcelCellClasses

If You want more flexible or more validation in ExcelCell class, You can simply extend **AbstractExcelCellClass** and create custom validations and return data types.
Int the example we will create cell that needs to be a valid email:

```php
<?php

namespace My\SampleNamespace;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractExcelCell;

class RegexValidatableExcelCell extends AbstractExcelCell
{

    /**
     * returned value will be returned by the getValue() method
     * Note, that getValue() will return this value only if cell doesn't contain any error
     */
    protected function getParsedValue(): ?string
    {
        // In this case, we don't want to do any parsing as string is proper data type for email address
        return $this->rawValue;
    }

    /**
     * Method should return null if value is valid,
     * or string with error message if not
     */
    protected function validateValueRequirements(): ?string
    {
        // We can access the raw string value with $this->rawValue
        // Note that the raw value will be null in case of empty cell
        if (filter_var($this->rawValue, FILTER_VALIDATE_EMAIL) === false) {
            
            // Below method creates error message in format [cellName] - [given_message]
            return $this->createErrorMessageWithNamePrefix('Value is not a valid email address');
        }
        
        return null;
    }
}
```

### More complex imports
Sometimes, we need to validate dependencies between cells inside a row, or even dependencies between rows.
We can do that as well. **AbstractExcelImporter** implements checkRow **checkRowRequirements()** method that can be overriden to check required dependencies and add some errors if needed.
It is called right before model creation in **AbstractModelExcelImporter**, so we can still be able to create object from EXCEL data.


Example of dependency validation:

Lets say we have some Model:

```php
<?php

namespace My\SampleNamespace;

use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\IntegerExcelCell;

class SampleModelClass
{
    /**
     * @ExcelColumn(cellName="Number 1", targetExcelCellClass=IntegerExcelCell::class, columnKey="A")
     *
     * @var int
     */
    private $num1;

    /**
     * @ExcelColumn(cellName="Number 1", targetExcelCellClass=IntegerExcelCell::class, columnKey="B")
     *
     * @var int
     */
    private $num2;


    public function getNum1(): int
    {
        return $this->num1;
    }
    public function setNum1(int $num1): void
    {
        $this->num1 = $num1;
    }
    public function getNum2(): int
    {
        return $this->num2;
    }
    public function setNum2(int $num2): void
    {
        $this->num2 = $num2;
    }
}
```

Let's assume, that num1 should be bigger than num2. We can validate this dependency like so:

```php
<?php

namespace My\SampleNamespace;

use Kczer\ExcelImporterBundle\AbstractModelExcelImporter;

class DependencyValidationExcelImport extends AbstractModelExcelImporter
{

    protected function checkRowRequirements(): void
    {
        foreach ($this->getExcelRows() as $excelRow) {
            $exclCells = $excelRow->getExcelCells();
            if ($exclCells['A']->getValue() <= $exclCells['B']->getValue()) {
                
                $excelRow->addErrorMessage('Number 1 should be bigger than Number 2');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function processParsedData(): void
    {
        // TODO: Implement processParsedData() method.
    }

    /**
     * @inheritDoc
     */
    protected function getImportModelClass(): string
    {
        return SampleModelClass::class;
    }
}
```

If validation adds any error, then excel will be considered invalid, therefore models **WILL NOT** be created.

### Data encoding

If you want to encode data from importer (for example to send it with request), You can do it like so:

```php
<?php
$rowsJson = $importer->getExcelRowsAsJson();
```

... and then re-create rows from this JSON:

```php
<?php
$importer->parseJson($rowsJson);
```