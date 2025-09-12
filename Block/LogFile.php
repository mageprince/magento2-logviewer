<?php
/**
 * MagePrince
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the mageprince.com license that is
 * available through the world-wide-web at this URL:
 * https://mageprince.com/end-user-license-agreement
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    MagePrince
 * @package     Mageprince_Faq
 * @copyright   Copyright (c) MagePrince (https://mageprince.com/)
 * @license     https://mageprince.com/end-user-license-agreement
 */

namespace Mageprince\LogViewer\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Mageprince\LogViewer\Model\FileViewer;

class LogFile extends Template
{
    /**
     * @var FileViewer
     */
    protected $fileViewer;

    /**
     * LogFile constructor.
     * @param Context $context
     * @param FileViewer $fileViewer
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        FileViewer $fileViewer,
        array $data = []
    ) {
        $this->fileViewer = $fileViewer;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve download log file url
     *
     * @param string $fileName
     * @return string
     */
    public function getDownloadLogFileUrl($fileName)
    {
        return $this->getUrl('logviewer/logfile/download', ['file' => $fileName]);
    }

    /**
     * Retrieve view log file url
     *
     * @param string $fileName
     * @return string
     */
    public function getViewLogFileUrl($fileName)
    {
        return $this->getUrl('logviewer/logfile/view', ['file' => $fileName]);
    }

    /**
     * Retrieve delete log file url
     *
     * @param string $fileName
     * @return string
     */
    public function getDeleteLogFile($fileName)
    {
        return $this->getUrl('logviewer/logfile/delete', ['file' => $fileName]);
    }

    /**
     * Retrieve load previous log url
     *
     * @return string
     */
    public function getLoadPreviousLogUrl()
    {
        return $this->getUrl('logviewer/logfile/loadprevious') . '?isAjax=true';
    }

    /**
     * Retrieve live log update url
     *
     * @return string
     */
    public function getLiveLogUrl()
    {
        return $this->getUrl('logviewer/logfile/liveupdate');
    }

    /**
     * Retrieve file name
     *
     * @return string
     */
    public function getFileName()
    {
        return $this->getRequest()->getParam('file');
    }

    /**
     * Retrieve file size
     *
     * @param string $bytes
     * @param string $precision
     * @return string
     */
    protected function filesizeToReadableString($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Retrieve log files
     *
     * @return array
     */
    public function getLogFiles()
    {
        $page = (int) $this->getRequest()->getParam('page', 1);
        $limit = $this->getItemsPerPageCount();
        $search = trim((string) $this->getRequest()->getParam('q', ''));
        $defaultSortColumn = $this->getDefaultSortColumn();
        $sort = $this->getRequest()->getParam('sort', $defaultSortColumn);
        $defaultSortDirection = $this->getDefaultSortDirection();
        $direction = strtolower($this->getRequest()->getParam('dir', $defaultSortDirection));

        $path = BP . '/var/log/';
        $files = [];
        $dir = new \DirectoryIterator($path);

        $allowedFileExtensions = $this->getAllowedFileExtensions();
        foreach ($dir as $fileInfo) {
            if (!$fileInfo->isDot()) {
                $fileName = $fileInfo->getFilename();
                if ($search && stripos($fileName, $search) === false) {
                    continue;
                }

                if ($allowedFileExtensions) {
                    $extension = strtolower(strrchr($fileName, '.'));
                    if (!in_array($extension, $allowedFileExtensions, true)) {
                        continue;
                    }
                }

                $files[] = [
                    'name' => $fileInfo->getFilename(),
                    'size' => $fileInfo->getSize(),
                    'size_readable' => $this->filesizeToReadableString($fileInfo->getSize()),
                    'mod_time' => $fileInfo->getMTime(),
                    'mod_time_full' => date("F d Y H:i:s.", $fileInfo->getMTime()),
                ];
            }
        }

        usort($files, function ($a, $b) use ($sort, $direction) {
            $result = $a[$sort] <=> $b[$sort];
            return $direction === 'desc' ? -$result : $result;
        });

        $total = count($files);
        $totalPages = ceil($total / $limit);
        $start = ($page - 1) * $limit;

        return [
            'items' => array_slice($files, $start, $limit),
            'page' => $page,
            'total' => $total,
            'totalPages' => $totalPages,
            'search' => $search,
            'sort' => $sort,
            'dir' => $direction,
        ];
    }

    /**
     * Retrieve file content
     *
     * @param string $filePath
     * @param int $lines
     * @return string
     */
    public function tailFile($filePath, $lines)
    {
        return $this->fileViewer->tailFile($filePath, $lines);
    }

    /**
     * Check if show pagination
     *
     * @param array $logs
     * @return bool
     */
    public function showPagination($logs)
    {
        $isShow = false;
        $limit = $this->getItemsPerPageCount();
        if ($logs['total'] > $limit) {
            $isShow = true;
        }
        return $isShow;
    }

    /**
     * Retrieve item per page count
     *
     * @return int
     */
    public function getItemsPerPageCount()
    {
        return (int)$this->_scopeConfig->getValue('log_viewer/general/items_per_page');
    }

    /**
     * Retrieve lines per page count
     *
     * @return int
     */
    public function getLinesToShowPerPageCount()
    {
        return (int)$this->_scopeConfig->getValue('log_viewer/general/lines_to_show');
    }

    /**
     * Retrieve default sort column
     *
     * @return string
     */
    public function getDefaultSortColumn()
    {
        return $this->_scopeConfig->getValue('log_viewer/general/default_sort_column');
    }

    /**
     * Retrieve default sort direction
     *
     * @return string
     */
    public function getDefaultSortDirection()
    {
        return $this->_scopeConfig->getValue('log_viewer/general/default_sort_dir');
    }

    /**
     * Retrieve allowed log file extensions
     *
     * @return array
     */
    public function getAllowedFileExtensions()
    {
        $extensions = [];
        $allowedExtensions = $this->_scopeConfig->getValue('log_viewer/general/allowed_extensions');
        if ($allowedExtensions) {
            $extensions = explode(',', $allowedExtensions);
        }
        return $extensions;
    }

    /**
     * Check is log can download
     *
     * @return bool
     */
    public function isDownloadAllowed()
    {
        return $this->_scopeConfig->isSetFlag('log_viewer/general/allow_download');
    }

    /**
     * Check is log can delete
     *
     * @return bool
     */
    public function isDeleteAllowed()
    {
        return $this->_scopeConfig->isSetFlag('log_viewer/general/allow_delete');
    }
}
