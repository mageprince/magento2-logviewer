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

        $columnName    = $this->getData('name');
        $allowDownload = $this->scopeConfig->isSetFlag(self::XML_PATH_ALLOW_DOWNLOAD);
        $viewLabel     = $this->escaper->escapeHtmlAttr((string)__('View'));
        $downloadLabel = $this->escaper->escapeHtmlAttr((string)__('Download'));

        foreach ($dataSource['data']['items'] as &$item) {
            if (empty($item['name'])) {
                continue;
            }

            $viewUrl = $this->escaper->escapeUrl(
                $this->urlBuilder->getUrl(self::VIEW_URL_PATH, ['file' => $item['name']])
            );

            $actionsHtml = '<div class="action-buttons">';
            $actionsHtml .= sprintf(
                '<a href="%s" class="logviewer-btn-view" title="%s" aria-label="%s">%s</a>',
                $viewUrl,
                $viewLabel,
                $viewLabel,
                $this->getViewIcon()
            );

            if ($allowDownload) {
                $downloadUrl = $this->escaper->escapeUrl(
                    $this->urlBuilder->getUrl(self::DOWNLOAD_URL_PATH, ['file' => $item['name']])
                );
                $actionsHtml .= sprintf(
                    '<a href="%s" class="logviewer-btn-download" title="%s" aria-label="%s">%s</a>',
                    $downloadUrl,
                    $downloadLabel,
                    $downloadLabel,
                    $this->getDownloadIcon()
                );
            }

            $actionsHtml .= '</div>';
            $item[$columnName] = $actionsHtml;
        }
        unset($item);

        return $dataSource;
    }

    /**
     * Return SVG eye icon for the View action.
     *
     * @return string
     */
    private function getViewIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"'
            . ' fill="none" stroke="currentColor" stroke-width="2"'
            . ' stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>'
            . '<circle cx="12" cy="12" r="3"/>'
            . '</svg>';
    }

    /**
     * Return SVG download icon for the Download action.
     *
     * @return string
     */
    private function getDownloadIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"'
            . ' fill="none" stroke="currentColor" stroke-width="2"'
            . ' stroke-linecap="round" stroke-linejoin="round">'
            . '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>'
            . '<polyline points="7 10 12 15 17 10"/>'
            . '<line x1="12" y1="15" x2="12" y2="3"/>'
            . '</svg>';
    }
}
