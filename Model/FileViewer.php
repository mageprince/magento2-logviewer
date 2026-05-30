<?php

namespace Mageprince\LogViewer\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Psr\Log\LoggerInterface;

class FileViewer
{
    private const GZIP_EXTENSION = '.gz';

    /**
     * @var File
     */
    protected $driver;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * FileViewer constructor.
     *
     * @param File $driver
     * @param LoggerInterface $logger
     */
    public function __construct(
        File $driver,
        LoggerInterface $logger
    ) {
        $this->driver = $driver;
        $this->logger = $logger;
    }

    /**
     * Tail log file
     *
     * @param string $filePath
     * @param int $lines
     * @param int $offset
     * @return string
     */
    public function tailFile($filePath, $lines, $offset = 0)
    {
        $output = [];

        try {
            if ($this->isReadable($filePath)) {
                if ($this->isGzipFile($filePath)) {
                    $content = $this->readFileContent($filePath);
                    if ($content === '') {
                        return '';
                    }

                    $linesArray = preg_split("/\r\n|\n|\r/", $content);
                    $needed = max(0, $offset + $lines);
                    $slice = array_slice($linesArray, -$needed, $lines);

                    return implode("\n", $slice);
                }

                $fp = $this->driver->fileOpen($filePath, 'rb');
                if ($fp === false) {
                    return '';
                }

                $this->driver->fileSeek($fp, 0, SEEK_END);
                $position = $this->driver->fileTell($fp);
                $chunk = '';
                $lineCount = 0;
                $buffer = 4096;
                $needed = $offset + $lines;

                while ($position > 0 && $lineCount <= $needed) {
                    $readSize = ($position - $buffer > 0) ? $buffer : $position;
                    $position -= $readSize;
                    $this->driver->fileSeek($fp, $position);

                    $chunk = $this->driver->fileRead($fp, $readSize) . $chunk;
                    $lineCount = substr_count($chunk, "\n");
                }

                $this->driver->fileClose($fp);

                $linesArray = explode("\n", $chunk);
                $slice = array_slice($linesArray, -$needed, $lines);
                $output = $slice;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return implode("\n", $output);
    }

    /**
     * Check if file has more data to load
     *
     * @param string $filePath
     * @param string $data
     * @param int $lines
     * @param int $offset
     * @return bool
     */
    public function hasMoreDataToLoad($filePath, $data, $lines, $offset)
    {
        if ($this->isGzipFile($filePath)) {
            $content = $this->readFileContent($filePath);
            if ($content === '') {
                return false;
            }

            $linesArray = preg_split("/\r\n|\n|\r/", $content);
            $totalLines = count($linesArray);

            return ($offset + $lines) < $totalLines;
        }

        $file = $this->driver->fileOpen($filePath, 'rb');
        $this->driver->fileSeek($file, 0, SEEK_END);
        $fileSize = $this->driver->fileTell($file);

        $this->driver->fileClose($file);
        $avgLineLength = max(strlen($data) / max($lines, 1), 1);
        $estimatedTotal = (int)($fileSize / $avgLineLength);

        return ($offset + $lines) < $estimatedTotal;
    }

    /**
     * Read file content from a given offset to the end
     *
     * @param string $filePath
     * @param int $offset
     * @return string
     */
    public function readFromOffset($filePath, $offset)
    {
        $content = '';
        try {
            if ($this->isReadable($filePath)) {
                if ($this->isGzipFile($filePath)) {
                    $content = substr($this->readFileContent($filePath), $offset);
                    return $content === false ? '' : $content;
                }

                $fp = $this->driver->fileOpen($filePath, 'rb');
                if ($fp === false) {
                    return '';
                }
                $this->driver->fileSeek($fp, $offset);
                $content = $this->driver->fileRead($fp, $this->getFileSize($filePath) - $offset);
                $this->driver->fileClose($fp);
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
        return $content;
    }

    /**
     * Check is file is readable
     *
     * @param string $fileName
     * @return bool
     * @throws FileSystemException
     */
    public function isReadable($fileName)
    {
        return $this->driver->isReadable($fileName);
    }

    /**
     * Retrieve file size
     *
     * @param string $filePath
     * @return mixed
     * @throws FileSystemException
     */
    public function getFileSize($filePath)
    {
        if ($this->isGzipFile($filePath)) {
            return strlen($this->readFileContent($filePath));
        }

        return $this->driver->stat($filePath)['size'];
    }

    /**
     * Check if file is gzip-compressed.
     *
     * @param string $filePath
     * @return bool
     */
    private function isGzipFile($filePath)
    {
        return strtolower(substr($filePath, -strlen(self::GZIP_EXTENSION))) === self::GZIP_EXTENSION;
    }

    /**
     * Read file content, decompressing gzip files into plain text.
     *
     * @param string $filePath
     * @return string
     */
    private function readFileContent($filePath)
    {
        if (!$this->isGzipFile($filePath)) {
            return (string)$this->driver->fileGetContents($filePath);
        }

        if (!function_exists('gzopen')) {
            return '';
        }

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $handle = gzopen($filePath, 'rb');
        if ($handle === false) {
            return '';
        }

        $content = '';

        try {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            while (!gzeof($handle)) {
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                $chunk = gzread($handle, 8192);
                if ($chunk === false) {
                    break;
                }

                $content .= $chunk;
            }
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            $content = '';
        } finally {
            gzclose($handle); // phpcs:ignore Magento2.Functions.DiscouragedFunction
        }

        return $content;
    }
}
