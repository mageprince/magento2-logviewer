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

namespace Mageprince\LogViewer\Controller\Adminhtml\LogFile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Mageprince\LogViewer\Model\FileViewer;

class LiveUpdate extends Action
{
    /**
     * @var FileViewer
     */
    protected $fileViewer;

    /**
     * @param Context $context
     * @param FileViewer $fileViewer
     */
    public function __construct(
        Action\Context $context,
        FileViewer $fileViewer
    ) {
        $this->fileViewer = $fileViewer;
        parent::__construct($context);
    }

    /**
     * Live file update action
     *
     * @return Json
     */
    public function execute()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->getUrl('logviewer/logfile/index'));
            return $resultRedirect;
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $file = $this->getRequest()->getParam('file');
        $lastSize = (int)$this->getRequest()->getParam('last_size', 0);
        $logPath = BP . '/var/log/' . $file;

        if (!$this->fileViewer->isReadable($logPath)) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('File not found or not readable')
            ]);
        }

        $currentSize = $this->fileViewer->getFileSize($logPath);
        $newContent = '';
        if ($currentSize > $lastSize) {
            $newContent = $this->fileViewer->readFromOffset($logPath, $lastSize);
        }

        return $resultJson->setData([
            'success' => true,
            'new_content' => $newContent,
            'current_size' => $currentSize
        ]);
    }
}
