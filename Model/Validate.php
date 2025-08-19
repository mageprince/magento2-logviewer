<?php

namespace Mageprince\LogViewer\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File as FileDriver;
use Magento\Framework\Filesystem\Io\File;

class Validate
{
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

        if (strpos($realPath, $logDir) !== 0) {
            return false;
        }

        if (!$this->fileDriver->isFile($realPath)) {
            return false;
        }

        $deniedExtensions = ['php', 'phtml', 'phar', 'exe', 'sh', 'bin', 'so', 'dll', 'pl', 'py', 'cgi'];
        $pathInfo = $this->file->getPathInfo($realPath);
        $extension = strtolower($pathInfo['extension']);
        if (in_array($extension, $deniedExtensions, true)) {
            return false;
        }

        return true;
    }
}
