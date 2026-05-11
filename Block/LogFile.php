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
 * @package     Mageprince_LogViewer
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
     * Retrieve lines per page count
     *
     * @return int
     */
    public function getLinesToShowPerPageCount()
    {
        return (int)$this->_scopeConfig->getValue('log_viewer/general/lines_to_show');
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
