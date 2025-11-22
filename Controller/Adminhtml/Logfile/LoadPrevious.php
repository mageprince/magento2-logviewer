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
use Magento\Framework\Controller\Result\JsonFactory;
use Mageprince\LogViewer\Model\FileViewer;

class LoadPrevious extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Mageprince_LogViewer::log_viewer_view';

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var FileViewer
     */
    protected $fileViewer;

    /**
     * LoadPrevious constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param FileViewer $fileViewer
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        FileViewer $fileViewer
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->fileViewer = $fileViewer;
        parent::__construct($context);
    }

    /**
     * Load logs action
     *
     * @return Json
     */
    public function execute()
    {
        if (!$this->getRequest()->isXmlHttpRequest()) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            $resultRedirect->setUrl($this->getUrl('logviewer/logfile/index'));
            return $resultRedirect;
        }

        $result = $this->jsonFactory->create();

        $file = $this->getRequest()->getParam('file');
        $offset = (int) $this->getRequest()->getParam('offset');
        $lines = (int) $this->getRequest()->getParam('lines');
        $filePath = BP . '/var/log/' . $file;

        $data = $this->fileViewer->tailFile($filePath, $lines, $offset);
        $hasMore = $this->fileViewer->hasMoreDataToLoad($filePath, $data, $lines, $offset);

        return $result->setData([
            'success' => true,
            'data' => $data,
            'has_more' => $hasMore
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
