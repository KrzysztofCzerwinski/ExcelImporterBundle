<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Util;

use Kczer\ExcelImporterBundle\Exception\FileLoadException;
use Kczer\ExcelImporterBundle\Exception\TemporaryFileManager\FileAlreadyExistsException;
use Kczer\ExcelImporterBundle\Exception\TemporaryFileManager\TemporaryFileCreationException;
use function fclose;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function fopen;
use function sprintf;
use function sys_get_temp_dir;
use function uniqid;

class TemporaryFileManager
{
    /** @var string|null */
    private $tmpFilePath;

    public function __destruct()
    {
        if (null !== $this->tmpFilePath && file_exists($this->tmpFilePath)) {
            unlink($this->tmpFilePath);
        }
    }

    /**
     * @return string Full path of newly created tmp file
     *
     * @throws FileAlreadyExistsException
     * @throws TemporaryFileCreationException
     */
    public function createTmpFileWithName(string $fileName): string
    {
        $this->tmpFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $fileName;
        if (file_exists($this->tmpFilePath)) {

            throw new FileAlreadyExistsException($this->tmpFilePath);
        }
        $fileResource = fopen($this->tmpFilePath, 'w');
        if (false === $fileResource) {

            throw new TemporaryFileCreationException($this->tmpFilePath);
        }
        fclose($fileResource);

        return $this->tmpFilePath;
    }

    /**
     * @param string|null $filenameWithoutExtension Random name provided in case of null
     * @param string $extension
     *
     * @return string
     *
     * @throws FileAlreadyExistsException
     * @throws TemporaryFileCreationException
     */
    public function createTmpFileWithNameAndExtension(?string $filenameWithoutExtension, string $extension): string
    {
        $fileName = sprintf(
            '%s.%s',
            null === $filenameWithoutExtension ? uniqid('excel_importer_', true) : $filenameWithoutExtension,
            $extension
        );

        return $this->createTmpFileWithName($fileName);
    }

    /**
     * @throws FileAlreadyExistsException
     * @throws TemporaryFileCreationException
     * @throws FileLoadException
     */
    public function createTmpFileWithExtensionFromExistingFile(?string $filenameWithoutExtension, string $extension, string $fileToCopyFullPath): string
    {
        if (!file_exists($fileToCopyFullPath)) {

            throw new FileLoadException($fileToCopyFullPath);
        }
        $fileName = $this->createTmpFileWithNameAndExtension($filenameWithoutExtension, $extension);
        file_put_contents($fileName, file_get_contents($fileToCopyFullPath));

        return $fileName;
    }
}