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

namespace Mageprince\LogViewer\Ui\DataProvider\LogFile;

use Magento\Framework\Api\Filter;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Ui\DataProvider\AbstractDataProvider;

class ListingDataProvider extends AbstractDataProvider
{
    private const XML_PATH_ALLOWED_EXTENSIONS = 'log_viewer/general/allowed_extensions';
    private const XML_PATH_DEFAULT_SORT_COLUMN = 'log_viewer/general/default_sort_column';
    private const XML_PATH_DEFAULT_SORT_DIR = 'log_viewer/general/default_sort_dir';
    private const XML_PATH_ITEMS_PER_PAGE = 'log_viewer/general/items_per_page';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var string
     */
    private $logDirectory;

    /**
     * @var Filter[]
     */
    private $filters = [];

    /**
     * @var array[]
     */
    private $orders = [];

    /**
     * @var int
     */
    private $currentPage = 1;

    /**
     * @var int
     */
    private $pageSize;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $directoryList
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        array $meta = [],
        array $data = []
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->logDirectory = rtrim($directoryList->getPath(DirectoryList::LOG), DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR;
        $this->pageSize = $this->getConfiguredItemsPerPage();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritdoc
     */
    public function addFilter(Filter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @inheritdoc
     */
    public function addOrder($field, $direction)
    {
        $this->orders[] = [
            'field' => $field,
            'direction' => strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC',
        ];
    }

    /**
     * @inheritdoc
     */
    public function setLimit($offset, $size)
    {
        $this->currentPage = max(1, (int)$offset);
        $this->pageSize = max(1, (int)$size);
    }

    /**
     * @inheritdoc
     */
    public function getData()
    {
        $items = $this->getLogFiles();
        $items = $this->applyFilters($items);
        $items = $this->applySorting($items);

        $totalRecords = count($items);
        $offset = ($this->currentPage - 1) * $this->pageSize;
        $items = array_slice($items, $offset, $this->pageSize);

        foreach ($items as &$item) {
            $item['size'] = $this->formatFileSize($item['size']);
            $item['mod_time'] = $this->formatTimestamp($item['mod_time']);
        }
        unset($item);

        return [
            'totalRecords' => $totalRecords,
            'items' => array_values($items),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getSearchCriteria()
    {
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getSearchResult()
    {
        return null;
    }

    /**
     * Retrieve total item count without relying on a DB-backed collection.
     *
     * @return int
     */
    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->applyFilters($this->getLogFiles()));
    }

    /**
     * Retrieve log files from var/log.
     *
     * @return array[]
     */
    private function getLogFiles()
    {
        if (!is_dir($this->logDirectory)) {
            return [];
        }

        $files = [];
        $allowedExtensions = $this->getAllowedFileExtensions();

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->logDirectory, \FilesystemIterator::SKIP_DOTS)
            );
        } catch (\UnexpectedValueException $exception) {
            return [];
        }

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }

            $relativePath = str_replace($this->logDirectory, '', $fileInfo->getPathname());
            $relativePath = ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $relativePath), '/');

            if (!$this->isAllowedExtension($fileInfo->getFilename(), $allowedExtensions)) {
                continue;
            }

            $files[] = [
                'entity_id' => md5($relativePath),
                'name' => $relativePath,
                'size' => (int)$fileInfo->getSize(),
                'mod_time' => (int)$fileInfo->getMTime(),
            ];
        }

        return $files;
    }

    /**
     * Apply UI grid filters.
     *
     * @param array[] $items
     * @return array[]
     */
    private function applyFilters(array $items)
    {
        if (!$this->filters) {
            return $items;
        }

        return array_values(array_filter($items, function (array $item) {
            foreach ($this->filters as $filter) {
                if (!$this->matchesFilter($item, $filter)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Apply UI grid sorting.
     *
     * @param array[] $items
     * @return array[]
     */
    private function applySorting(array $items)
    {
        $sortField = $this->getDefaultSortColumn();
        $sortDirection = $this->getDefaultSortDirection();

        if ($this->orders) {
            $order = reset($this->orders);
            $sortField = $this->isSortableField($order['field']) ? $order['field'] : $sortField;
            $sortDirection = $order['direction'];
        }

        usort($items, function (array $left, array $right) use ($sortField, $sortDirection) {
            switch ($sortField) {
                case 'name':
                    $result = strcasecmp($left['name'], $right['name']);
                    break;

                case 'size':
                    $result = $left['size'] <=> $right['size'];
                    break;

                case 'mod_time':
                default:
                    $result = $left['mod_time'] <=> $right['mod_time'];
                    break;
            }

            if ($result === 0) {
                $result = strcasecmp($left['name'], $right['name']);
            }

            return $sortDirection === 'DESC' ? -$result : $result;
        });

        return $items;
    }

    /**
     * Check whether an item matches a filter.
     *
     * @param array $item
     * @param Filter $filter
     * @return bool
     */
    private function matchesFilter(array $item, Filter $filter)
    {
        $field = $filter->getField();
        $condition = $filter->getConditionType() ?: 'eq';
        $value = $filter->getValue();

        if ($field === 'fulltext') {
            return stripos($item['name'], (string)$value) !== false;
        }

        if ($field === 'name') {
            if ($condition === 'like') {
                return $this->matchesLike($item['name'], (string)$value);
            }

            if ($condition === 'eq') {
                return strcmp($item['name'], (string)$value) === 0;
            }
        }

        if ($field === 'size') {
            $size = (int)$item['size'];
            $value = (int)$value;

            if ($condition === 'gteq') {
                return $size >= $value;
            }

            if ($condition === 'lteq') {
                return $size <= $value;
            }

            if ($condition === 'eq') {
                return $size === $value;
            }
        }

        if ($field === 'mod_time') {
            $formattedTime = $this->formatTimestamp($item['mod_time']);

            if ($condition === 'like') {
                return $this->matchesLike($formattedTime, (string)$value);
            }

            if ($condition === 'eq') {
                return strcmp($formattedTime, (string)$value) === 0;
            }
        }

        return true;
    }

    /**
     * Convert SQL LIKE syntax to a regex comparison.
     *
     * @param string $subject
     * @param string $pattern
     * @return bool
     */
    private function matchesLike($subject, $pattern)
    {
        $regex = '';
        $length = strlen($pattern);
        $isEscaped = false;

        for ($i = 0; $i < $length; $i++) {
            $character = $pattern[$i];

            if ($isEscaped) {
                $regex .= preg_quote($character, '/');
                $isEscaped = false;
                continue;
            }

            if ($character === '\\') {
                $isEscaped = true;
                continue;
            }

            if ($character === '%') {
                $regex .= '.*';
                continue;
            }

            if ($character === '_') {
                $regex .= '.';
                continue;
            }

            $regex .= preg_quote($character, '/');
        }

        if ($isEscaped) {
            $regex .= '\\\\';
        }

        return (bool)preg_match('/^' . $regex . '$/iu', $subject);
    }

    /**
     * Retrieve configured items per page.
     *
     * @return int
     */
    private function getConfiguredItemsPerPage()
    {
        return max(1, (int)$this->scopeConfig->getValue(self::XML_PATH_ITEMS_PER_PAGE));
    }

    /**
     * Retrieve default sort column.
     *
     * @return string
     */
    private function getDefaultSortColumn()
    {
        $field = (string)$this->scopeConfig->getValue(self::XML_PATH_DEFAULT_SORT_COLUMN);

        return $this->isSortableField($field) ? $field : 'mod_time';
    }

    /**
     * Retrieve default sort direction.
     *
     * @return string
     */
    private function getDefaultSortDirection()
    {
        return strtolower((string)$this->scopeConfig->getValue(self::XML_PATH_DEFAULT_SORT_DIR)) === 'asc'
            ? 'ASC'
            : 'DESC';
    }

    /**
     * Check whether a field is sortable.
     *
     * @param string $field
     * @return bool
     */
    private function isSortableField($field)
    {
        return in_array($field, ['name', 'size', 'mod_time'], true);
    }

    /**
     * Retrieve configured allowed extensions.
     *
     * @return string[]
     */
    private function getAllowedFileExtensions()
    {
        $configuredExtensions = (string)$this->scopeConfig->getValue(self::XML_PATH_ALLOWED_EXTENSIONS);

        if ($configuredExtensions === '') {
            return [];
        }

        $extensions = array_map('trim', explode(',', $configuredExtensions));
        $extensions = array_filter($extensions, function ($extension) {
            return $extension !== '';
        });

        return array_map(function ($extension) {
            $extension = strtolower($extension);
            return strpos($extension, '.') === 0 ? $extension : '.' . $extension;
        }, $extensions);
    }

    /**
     * Check whether a filename matches the configured extension allow-list.
     *
     * @param string $fileName
     * @param string[] $allowedExtensions
     * @return bool
     */
    private function isAllowedExtension($fileName, array $allowedExtensions)
    {
        if (!$allowedExtensions) {
            return true;
        }

        $extension = strtolower((string)strrchr($fileName, '.'));

        return in_array($extension, $allowedExtensions, true);
    }

    /**
     * Format a file size for display.
     *
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    private function formatFileSize($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max((int)$bytes, 0);
        $power = (int)floor(($bytes ? log($bytes) : 0) / log(1024));
        $power = min($power, count($units) - 1);
        $bytes /= pow(1024, $power);

        return round($bytes, $precision) . ' ' . $units[$power];
    }

    /**
     * Format a file modification timestamp for display.
     *
     * @param int $timestamp
     * @return string
     */
    private function formatTimestamp($timestamp)
    {
        return date('F d Y H:i:s', (int)$timestamp);
    }
}
