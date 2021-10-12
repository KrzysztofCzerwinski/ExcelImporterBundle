<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Exporter;

use Kczer\ExcelImporterBundle\Exception\Annotation\AnnotationConfigurationException;
use Kczer\ExcelImporterBundle\Exception\ExcelImportConfigurationException;
use Kczer\ExcelImporterBundle\Model\Factory\ModelMetadataFactory;
use Kczer\ExcelImporterBundle\Model\ModelMetadata;
use ReflectionException;
use function current;
use function get_class;

class ModelExcelExporter
{
    /** @var ModelMetadataFactory */
    private $modelMetadataFactory;

    /** @var object[] */
    private $models = [];

    /** @var ModelMetadata */
    private $modelMetadata;

    public function __construct(
        ModelMetadataFactory $modelMetadataFactory
    )
    {
        $this->modelMetadataFactory = $modelMetadataFactory;
    }

    /**
     * @throws ReflectionException
     * @throws AnnotationConfigurationException
     * @throws ExcelImportConfigurationException
     */
    public function exportModel(array $models): void
    {
        $this->models = $models;
        $this->assignModelMetadata();
    }

    /**
     * @throws ReflectionException
     * @throws ExcelImportConfigurationException
     * @throws AnnotationConfigurationException
     */
    protected function assignModelMetadata(): void
    {
        $this->modelMetadata = $this->modelMetadataFactory->createMetadataFromModelClass(get_class(current($this->models)), null);
    }
}