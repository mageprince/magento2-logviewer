<?php

namespace Mageprince\LogViewer\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\Io\File;

class Validate
{
    private const ALLOWED_EXTENSIONS = ['log', 'zip', 'tar', 'gz'];

    /**
     * @var File
     */
    protected $file;

    /**
     * @var FileDriver
     */
    protected $fileDriver;

    /**
     * Validate constructor.
     * @param FileDriver $fileDriver
     * @param File $file
     */
    public function __construct(
        FileDriver $fileDriver,
        File $file
    ) {
        $this->file = $file;
        $this->fileDriver = $fileDriver;
    }

    /**
     * Check file is valid
     *
     * @param string $fileName
     * @return bool
     * @throws FileSystemException
     */
    public function validateFile(string $fileName)
    {
        $logDir = $this->fileDriver->getRealPath(BP . '/var/log') . DIRECTORY_SEPARATOR;
        $realPath = $this->fileDriver->getRealPath($logDir . $fileName);

        if ($realPath === false || strpos($realPath, $logDir) !== 0) {
            return false;
        }

        if (!$this->fileDriver->isFile($realPath)) {
            return false;
        }

        $pathInfo = $this->file->getPathInfo($realPath);
        $extension = strtolower($pathInfo['extension'] ?? '');
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            return false;
        }

        return true;
    }

    /**
     * Retrieve allowed extensions formatted for display.
     *
     * @return string
     */
    public function getAllowedExtensionsMessage()
    {
        $extensions = array_map(function ($extension) {
            return "'" . $extension . "'";
        }, self::ALLOWED_EXTENSIONS);

        return implode(', ', $extensions);
    }
}
