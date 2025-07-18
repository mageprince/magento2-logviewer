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
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Filesystem\Driver\File;

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
     * @var File
     */
    protected $driver;

    /**
     * LoadPrevious constructor.
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param File $driver
     */
    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        File $driver,
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->driver = $driver;
        parent::__construct($context);
    }

    /**
     * Load logs action
     *
     * @return Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $file = $this->getRequest()->getParam('file');
        $offset = (int) $this->getRequest()->getParam('offset');
        $lines = (int) $this->getRequest()->getParam('lines');
        $logPath = BP . '/var/log/' . $file;

        $data = [];
        $hasMore = false;

        if ($this->driver->isReadable($logPath)) {
            $file = new \SplFileObject($logPath, 'r');
            $file->seek(PHP_INT_MAX);
            $totalLines = $file->key();

            $from = max(0, $totalLines - $offset - $lines);
            $safeLines = min($lines, $totalLines - $from);

            $file->seek($from);
            $readLines = 0;

            while (!$file->eof() && $readLines < $safeLines) {
                $data[] = rtrim($file->fgets(), "\n");
                $readLines++;
            }

            $hasMore = ($offset + $lines) < $totalLines;
        }

        return $result->setData([
            'success' => true,
            'data' => implode("\n", $data),
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
