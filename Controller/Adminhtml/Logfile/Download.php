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

use Magento\Backend\App\Action\Context;
use Magento\Backend\Controller\Adminhtml\System;
use Magento\Framework\App\Response\Http\FileFactory;
use Mageprince\LogViewer\Model\Validate;

class Download extends System
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Mageprince_LogViewer::log_viewer_download';

    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * Download constructor.
     * @param Context $context
     * @param FileFactory $fileFactory
     */
    public function __construct(
        Context $context,
        FileFactory $fileFactory
    ) {
        $this->fileFactory = $fileFactory;
        parent::__construct($context);
    }

    /**
     * Delete action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $fileName = $this->getRequest()->getParam('file');
        $filePath = 'var/log/'. $fileName;

        try {
            return $this->fileFactory->create(
                $fileName,
                [
                    'type'  => 'filename',
                    'value' => $filePath
                ]
            );
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('File not found'));
        }
        return $this->_redirect('logviewer/logfile/index');
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
