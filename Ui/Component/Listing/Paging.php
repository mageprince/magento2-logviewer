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

namespace Mageprince\LogViewer\Ui\Component\Listing;

use Mageprince\LogViewer\Model\Config\Source\ListPerPage;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;

class Paging extends \Magento\Ui\Component\Paging
{
    private const XML_PATH_ITEMS_PER_PAGE = 'log_viewer/general/items_per_page';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var ListPerPage
     */
    private $listPerPage;

    /**
     * @param ContextInterface $context
     * @param ScopeConfigInterface $scopeConfig
     * @param ListPerPage $listPerPage
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        ScopeConfigInterface $scopeConfig,
        ListPerPage $listPerPage,
        array $components = [],
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->listPerPage = $listPerPage;
        parent::__construct($context, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        $config = $this->getData('config');
        $pageSize = max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_ITEMS_PER_PAGE));

        $config['pageSize'] = $pageSize;
        $config['options'] = $this->getPageSizeOptions($pageSize);

        $this->setData('config', $config);

        parent::prepare();
    }

    /**
     * Build page-size options for the pager selector.
     *
     * @param int $pageSize
     * @return array
     */
    private function getPageSizeOptions($pageSize)
    {
        $options = [];

        foreach ($this->listPerPage->toOptionArray() as $option) {
            $value = (int)$option['value'];
            $options[(string)$value] = [
                'value' => $value,
                'label' => (string)$option['label'],
            ];
        }

        if (!isset($options[(string)$pageSize])) {
            $options[(string)$pageSize] = [
                'value' => $pageSize,
                'label' => (string)$pageSize,
            ];
        }

        ksort($options, SORT_NUMERIC);

        return $options;
    }
}
