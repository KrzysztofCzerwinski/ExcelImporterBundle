<?php
declare(strict_types=1);

namespace Kczer\ExcelImporterBundle\Util;

use Kczer\ExcelImporterBundle\Exception\TemporaryFileManager\FileAlreadyExistsException;
use Kczer\ExcelImporterBundle\Exception\TemporaryFileManager\TemporaryFileCreationException;
use function fclose;
use function file_exists;
use function fopen;
use function sys_get_temp_dir;

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
}