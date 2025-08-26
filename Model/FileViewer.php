<?php

namespace Mageprince\LogViewer\Model;

use Magento\Framework\Filesystem\Driver\File;

class FileViewer
{
    /**
     * @var File
     */
    protected $driver;

    /**
     * @param File $driver
     */
    public function __construct(
        File $driver,
    ) {
        $this->driver = $driver;
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
            if ($this->driver->isReadable($filePath)) {
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
            $this->_logger->error($e->getMessage());
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
        $file = $this->driver->fileOpen($filePath, 'rb');
        $this->driver->fileSeek($file, 0, SEEK_END);
        $fileSize = $this->driver->fileTell($file);

        $this->driver->fileClose($file);
        $avgLineLength = max(strlen($data) / max($lines, 1), 1);
        $estimatedTotal = (int)($fileSize / $avgLineLength);

        return ($offset + $lines) < $estimatedTotal;
    }
}
