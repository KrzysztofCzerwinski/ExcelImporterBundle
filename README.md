### Table of contents:
- [Excel-importer](#excel-importer)
    * [Installation](#installation)
    * [Documentation](#documentation)
        + [Creating simple EXCEL data import](#creating-simple-excel-data-import)
        + [Mapping EXCEL data to objects](#mapping-excel-data-to-objects)
        + [DictionaryExcelCell](#dictionaryexcelcell)
        + [MultipleDictionaryExcelCell](#multipledictionaryexcelcell)
        + [Custom ExcelCellClasses](#custom-excelcellclasses)
        + [More complex imports](#more-complex-imports)
        + [Data encoding](#data-encoding)

# ExcelImpoerterBundle

Excel-importer is a PHP library that enables to easy import EXCEL formats data and event parse it to objects.

## Installation
You can install it with composer like so:

```bash
composer require kczer/excel-importer-bundle
```


## Documentation


### Creating simple EXCEL data import

First you need to create Import class that extends **Kczer\ExcelImporter\AbstractExcelImporter**.

For version >= 3.0:

```php
<?php

namespace My\SampleNamespace;

use Kczer\ExcelImporterBundle\AbstractExcelImporter;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration\ExcelCellConfiguration;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\IntegerExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\StringExcelCell;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;

class MySimpleExcelImporter extends AbstractExcelImporter
{
    protected function configureExcelCells(): void
    {
        $this
            ->addExcelCell(StringExcelCell::class, 'Cell header name', 'A', false)
            ->addExcelCell(IntegerExcelCell::class, 'Another header name', 'B');
    }
        
    public function processParsedData(): void
    {
        // Since 3.1, you can pass callback as first argument to this function to modify the message
        // Separator is now the second parameter
        $excelRow->getMergedErrorMessage(static function(string $message): string {
            //Do something with the message
        }); 
        // The rest is same as before 3.0
    }
}
````

For version < 3.0

``` php
<?php

namespace My\SampleNamespace;

use Kczer\ExcelImporterBundle\AbstractExcelImporter;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\Configuration\ExcelCellConfiguration;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\IntegerExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\StringExcelCell;
use Kczer\ExcelImporterBundle\Exception\ExcelCellConfiguration\UnexpectedExcelCellClassException;

class MySimpleExcelImporter extends AbstractExcelImporter
{

    /**
     * @return ExcelCellConfiguration[]
     */
    protected function getExcelCellConfigurations(): array
    {
        // This method returns row configuration for Your import.
        // Keys are column keys (can be int indexed but it's not reccommended).
        /*
            Values are configurations that needs to be pased AbstractExcelClass
            extending class names, column names, and (optinoally) 
            tell if cell is required (with default to true).
        */
        return [
            'A' => new ExcelCellConfiguration(StringExcelCell::class, 'Cell header name', false),
            'B' => new ExcelCellConfiguration(IntegerExcelCell::class, 'Another header name'),
        ];
    }

    public function processParsedData(): void
    {
        $this->hasErrors(); // Check if any validation error occurred
        
        // We can do something with Data. 
        
        // Gets an array of ExcelRow objects
        $excelRows = $this->getExcelRows(); 
        foreach ($excelRows as $excelRow) {
            // The same as above for one row
            $excelRow->hasErrors(); 
            // Gets merged messages from all cells for current row by separator given in argument (default ' | ')
            $excelRow->getMergedErrorMessage(); 
            // Gets an array of ExcelCells (the same as configured in getExcelCellConfigurations())
            $excelCells = $excelRow->getExcelCells(); 
            // Gets value of current row 'A' cell
            $excelCells['A']->getValue(); 
            // Gets int-parsed value of current row 'B' cell
            $excelCells['B']->getValue(); 
            // Gets string representation of value ready to dispaly (the same value as given in EXCEL file for predefined ExcelCells)
            $excelCells['B']->getDisplayValue();
        }
    }
}
```

> :warning: **Since version 3.0 int column keys are no longer supported**.

Then using the service:

```php
<?php

use My\SampleNamespace\MySimpleExcelImporter;

$importer = new MySimpleExcelImporter();

// Second parameter tells whether to omit first row, or not (with default to true)
$importer->parseExcelData('some/file/path/excelFile.xlsx', true);
$importer->processParsedData();
```

> :warning: **Since version 3.0 importer always omits first row**.

All methods shown in **processParsedData()** are public and therefore can be accessed from outside the service.
As You could see in Configuration above there StringExcelCell and IntegerExcelCell classes used. These classes are used to perform proper parsing and validations on EXCEL cell values.
You can crate Your own ExcelCell classes- more info below.

Predefined ExcelCell classes:
- **StringExcelCell** - Simple string values with no validation of data. getValue() returns string.
- **IntegerExcelCell** - Accepts only valid ints. getValue() returns int.
- **FloatExcelCell** - Accepts only valid numbers. getValue() return float.
- **BoolExcelCell** - Accepts 'y', 'yes', 't', 'tak', 't', 'true' (case insensitive) as true. Other values are considered false. getValue() returns bool.
- **DateTimeExcelCell** - Accepts all strings acceptable by DateTime class constructor. getValue() returns DataTime object
- **AbstractDictionaryExcelCell** - Abstract class useful for key-value types example below
- **AbstractMultipleDictionaryExcelCell** - Abstract class that can merge a couple of AbstractDictionaryCell dictionaries into one


Congratulations! You created your first simple EXCEL import class. Although, there is a better and faster way of doing imports with Excel-importer.

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

```php
<?php

namespace My\SampleNamespace;

use DateTime;
use Kczer\ExcelImporterBundle\Annotation\ExcelColumn;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\StringExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\IntegerExcelCell;
use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\DateTimeExcelCell;

class SomeModel
{
    
    /** 
     * @ExcelColumn(cellName="Name", targetExcelCellClass=StringExcelCell::class, columnKey="A")
     * 
     * @var string 
     */
    private $name;

    /** 
     * @ExcelColumn(cellName="Code", targetExcelCellClass=IntegerExcelCell::class, columnKey="B")
     * 
     * @var int 
     */
    private $code;
    
    /**
     * @ExcelColumn(cellName="Some date", targetExcelCellClass=DateTimeExcelCell::class, columnKey="C", required=false)
     * 
     * @var  ?DateTime
     */
    private $someDate;

    
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    public function getSomeDate(): ?DateTime
    {
        return $this->someDate;
    }

    public function setSomeDate(?DateTime $someDate): void
    {
        $this->someDate = $someDate;
    }
}
```

The most important thing in code below is **ExcelColumn** annotation class, that tells the importer everything about our model data type, name, column key (again, key column can be omitted, and then properties order is taken, but it's not considered) and cell obligatory.
These two classes is everything you need to do. Excel importer will do the rest and create Your model instances for You.


### DictionaryExcelCell

Dictionary EXCEL cells are used to define "range" of values, that cell can have.
It's perfect when cell value can be for example id of some resource from database.
Sample DictionaryExcelCell class:

```php
<?php

namespace My\SampleNamespace;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractDictionaryExcelCell;

class SampleDictionaryExcelCell extends AbstractDictionaryExcelCell
{

    /**
     * @inheritDoc
     */
    protected function getDictionary(): array
    {
       return [
           1 => new User('user 1'),
           2 => new User('user 2'),
           3 => new User('user 3'),
           4 => new User('user 4'),
       ];
    }
}
```

Now, we could just add this class to Import configuration of to **ExcelColumn** annotation, and excel-importer will accept only values from range 1-4 and getValue will return User objects.

### MultipleDictionaryExcelCell
Let's say that one column can contain either value from one dictionary or from another.
MultipleDictionaryExcelCell is a perfect tool for such situation:

```php
<?php

namespace My\SampleNamespace;

use Kczer\ExcelImporterBundle\ExcelElement\ExcelCell\AbstractMultipleDictionaryExcelCell;

class SampleMultidictionaryExcelCell extends AbstractMultipleDictionaryExcelCell
{

    /**
     * @inheritDoc
     */
    public function getSubDictionaryExcelCellClasses(): array
    {
        return [
            SomeDictionaryClass::class,
            SomeOtherDictionaryClasss::class
        ];
    }
}
```

Now, excel-import accepts values from both SomeDictionaryClass and SomeOtherDictionaryClasss dictionaries (if keys intersect, class with lower array key has priority).

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
$serializedInstance = $importer->serializeInstance();
```

... and then re-create importer instance:

```php
<?php
MyImporterClass::createFromSerialized($serializedInstance);
```

Since version 3.0, it is also possible to get rows as JSON:

```php
<?php
$rowsJson = $importer->getExcelRowsAsJson();
```

... and then re-create rows from this JSON:

```php
<?php
$importer->parseJson($rowsJson);
```