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

namespace Mageprince\LogViewer\Controller\Adminhtml\Logfile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Mageprince\LogViewer\Model\Config;
use Mageprince\LogViewer\Model\FileViewer;
use Mageprince\LogViewer\Model\Validate;

class LiveUpdate extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Mageprince_LogViewer::log_viewer_view';

    /**
     * @var FileViewer
     */
    protected $fileViewer;

    /**
     * @var Validate
     */
    protected $validate;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @param Context $context
     * @param FileViewer $fileViewer
     * @param Validate $validate
     * @param Config $config
     */
    public function __construct(
        Action\Context $context,
        FileViewer $fileViewer,
        Validate $validate,
        Config $config
    ) {
        $this->fileViewer = $fileViewer;
        $this->validate = $validate;
        $this->config = $config;
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
        $file = (string)$this->getRequest()->getParam('file', '');
        $lastSize = max(0, (int)$this->getRequest()->getParam('last_size', 0));

        try {
            $isValid = $this->validate->validateFile($file);
        } catch (\Exception $e) {
            $isValid = false;
        }

        if (!$isValid) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Invalid file.')
            ]);
        }

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

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->config->isEnabled() && $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
