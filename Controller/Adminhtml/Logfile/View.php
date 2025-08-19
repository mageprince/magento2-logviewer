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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;
use Mageprince\LogViewer\Model\Validate;

class View extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Mageprince_LogViewer::log_viewer_view';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Validate
     */
    protected $validate;

    /**
     * View constructor.
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Validate $validate
     */
    public function __construct(
        Action\Context $context,
        PageFactory $resultPageFactory,
        Validate $validate
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->validate = $validate;
        parent::__construct($context);
    }

    /**
     * View file action
     *
     * @return ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Magento_Backend::stores');
        try {
            $fileName = $this->getRequest()->getParam('file');
            $isValid = $this->validate->validateFile($fileName);
            if (!$isValid) {
                $this->messageManager->addErrorMessage(__('Invalid file'));
                return $this->_redirect('logviewer/logfile/index');
            }
            $resultPage->getConfig()->getTitle()->prepend(__('Log Viewer (%1)', $fileName));
            return $resultPage;
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('File not found'));
        }
        $this->_redirect('logviewer/logfile/index');
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
