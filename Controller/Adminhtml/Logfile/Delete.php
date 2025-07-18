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

namespace Mageprince\LogViewer\Controller\Adminhtml\Logfile;

use Mageprince\LogViewer\Block\LogFile;
use Mageprince\LogViewer\Helper\Data;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\System;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Filesystem\Driver\File;

class Delete extends System
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Mageprince_LogViewer::log_viewer_delete';

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var LogFile
     */
    protected $logFile;

    /**
     * @var File
     */
    protected $driver;

    /**
     * Delete constructor.
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param LogFile $logFile
     * @param File $driver
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory,
        LogFile $logFile,
        File $driver
    ) {
        $this->fileFactory = $fileFactory;
        $this->logFile = $logFile;
        $this->driver = $driver;
        parent::__construct($context);
    }

    /**
     * Delete action
     *
     * @return void
     */
    public function execute()
    {
        $fileName = $this->getRequest()->getParam('file');
        $file = BP . '/var/log/' . $fileName;
        $fp = $this->driver->fileOpen($file, "r+");
        ftruncate($fp, 0);
        $this->driver->fileClose($fp);
        $this->messageManager->addSuccessMessage(__("File content of %1 has been deleted", $fileName));
        $this->_redirect('logviewer/logfile/view', ['file' => $fileName]);
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
