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

namespace Mageprince\LogViewer\Ui\Component\Listing\Column;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Actions extends Column
{
    private const XML_PATH_ALLOW_DOWNLOAD = 'log_viewer/general/allow_download';
    private const VIEW_URL_PATH = 'logviewer/logfile/view';
    private const DOWNLOAD_URL_PATH = 'logviewer/logfile/download';

    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param ScopeConfigInterface $scopeConfig
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        ScopeConfigInterface $scopeConfig,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->scopeConfig = $scopeConfig;
        $this->escaper = $escaper;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        $columnName = $this->getData('name');
        $allowDownload = $this->scopeConfig->isSetFlag(self::XML_PATH_ALLOW_DOWNLOAD);

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['name'])) {
                continue;
            }

            $actionsHtml = '<div class="action-buttons">';
            $actionsHtml .= sprintf(
                '<a href="%s" class="action-primary">%s</a>',
                $this->escaper->escapeUrl($this->urlBuilder->getUrl(self::VIEW_URL_PATH, ['file' => $item['name']])),
                $this->escaper->escapeHtml((string)__('View'))
            );

            if ($allowDownload) {
                $actionsHtml .= sprintf(
                    '<a href="%s" class="action-secondary">%s</a>',
                    $this->escaper->escapeUrl(
                        $this->urlBuilder->getUrl(self::DOWNLOAD_URL_PATH, ['file' => $item['name']])
                    ),
                    $this->escaper->escapeHtml((string)__('Download'))
                );
            }

            $actionsHtml .= '</div>';
            $item[$columnName] = $actionsHtml;
        }
        unset($item);

        return $dataSource;
    }
}
