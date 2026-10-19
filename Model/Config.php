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

namespace Mageprince\LogViewer\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    public const XML_PATH_ENABLE = 'log_viewer/general/enable';
    public const XML_PATH_LINES_TO_SHOW = 'log_viewer/general/lines_to_show';
    public const XML_PATH_ALLOW_DELETE = 'log_viewer/general/allow_delete';
    public const XML_PATH_ALLOW_DOWNLOAD = 'log_viewer/general/allow_download';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Check module is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLE);
    }

    /**
     * Retrieve configured lines per page (at least 1)
     *
     * @return int
     */
    public function getLinesToShow()
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_LINES_TO_SHOW));
    }

    /**
     * Check file truncation is allowed
     *
     * @return bool
     */
    public function isDeleteAllowed()
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_PATH_ALLOW_DELETE);
    }

    /**
     * Check file download is allowed
     *
     * @return bool
     */
    public function isDownloadAllowed()
    {
        return $this->isEnabled() && $this->scopeConfig->isSetFlag(self::XML_PATH_ALLOW_DOWNLOAD);
    }
}
