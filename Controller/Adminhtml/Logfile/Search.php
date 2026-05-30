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
use Mageprince\LogViewer\Model\Validate;

class Search extends Action
{
    /**
     * Authorization level of a basic admin session
     */
    public const ADMIN_RESOURCE = 'Mageprince_LogViewer::log_viewer_view';

    /**
     * Number of matching lines returned per page
     */
    private const LINES_PER_PAGE = 20;

    /**
     * Maximum allowed search query length (characters)
     */
    private const MAX_QUERY_LENGTH = 500;

    /**
     * @var Validate
     */
    private $validate;

    /**
     * @param Context $context
     * @param Validate $validate
     */
    public function __construct(
        Context $context,
        Validate $validate
    ) {
        $this->validate = $validate;
        parent::__construct($context);
    }

    /**
     * Full-file search action
     *
     * @return Json
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $fileName   = (string)$this->getRequest()->getParam('file', '');
        $query      = $this->sanitizeQuery((string)$this->getRequest()->getParam('query', ''));
        $beforeLine = $this->getRequest()->getParam('before_line');

        if (!$this->validate->validateFile($fileName)) {
            return $resultJson->setData(['success' => false, 'message' => __('Invalid file.')]);
        }

        if ($query === '') {
            return $resultJson->setData(['success' => true, 'matches' => [], 'has_more' => false]);
        }

        if (mb_strlen($query) > self::MAX_QUERY_LENGTH) {
            return $resultJson->setData(['success' => false, 'message' => __('Search query is too long.')]);
        }

        try {
            $filePath      = BP . '/var/log/' . $fileName;
            $caseSensitive = (bool)$this->getRequest()->getParam('case_sensitive', 0);
            $isGzip        = strtolower(substr($fileName, -3)) === '.gz';

            // Clamp before_line to a valid positive line number
            $parsedLine = $beforeLine !== null ? max(1, (int)$beforeLine) : null;

            if ($parsedLine !== null) {
                [$matches, $hasMore] = $isGzip
                    ? $this->searchGzipBefore($filePath, $query, $caseSensitive, $parsedLine)
                    : $this->searchPlainBefore($filePath, $query, $caseSensitive, $parsedLine);
            } else {
                [$matches, $hasMore] = $isGzip
                    ? $this->searchGzipLast($filePath, $query, $caseSensitive)
                    : $this->searchPlainLast($filePath, $query, $caseSensitive);
            }

            return $resultJson->setData([
                'success'       => true,
                'matches'       => $matches,
                'has_more'      => $hasMore,
                'earliest_line' => $matches ? $matches[0]['line'] : 0,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData(['success' => false, 'message' => __('Error reading file.')]);
        }
    }

    /**
     * Return the last LINES_PER_PAGE matching lines from a plain-text file.
     *
     * Uses a rolling window so memory stays constant regardless of file size.
     *
     * @param string $filePath
     * @param string $query
     * @param bool $caseSensitive
     * @return array{0: array, 1: bool}
     */
    private function searchPlainLast(string $filePath, string $query, bool $caseSensitive): array
    {
        $pattern = $this->buildPattern($query, $caseSensitive);
        $window  = [];
        $total   = 0;

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [[], false];
        }

        $lineNum = 0;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        while (($line = fgets($handle)) !== false) {
            $lineNum++;
            if (preg_match($pattern, $line)) {
                $total++;
                $window[] = ['line' => $lineNum, 'content' => rtrim($line, "\r\n")];
                if (count($window) > self::LINES_PER_PAGE) {
                    array_shift($window);
                }
            }
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        fclose($handle);

        return [$window, $total > self::LINES_PER_PAGE];
    }

    /**
     * Return the last LINES_PER_PAGE matching lines that appear before $beforeLine.
     *
     * @param string $filePath
     * @param string $query
     * @param bool $caseSensitive
     * @param int $beforeLine
     * @return array{0: array, 1: bool}
     */
    private function searchPlainBefore(
        string $filePath,
        string $query,
        bool $caseSensitive,
        int $beforeLine
    ): array {
        $pattern = $this->buildPattern($query, $caseSensitive);
        $window  = [];
        $total   = 0;

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return [[], false];
        }

        $lineNum = 0;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        while (($line = fgets($handle)) !== false) {
            $lineNum++;
            if ($lineNum >= $beforeLine) {
                break;
            }
            if (preg_match($pattern, $line)) {
                $total++;
                $window[] = ['line' => $lineNum, 'content' => rtrim($line, "\r\n")];
                if (count($window) > self::LINES_PER_PAGE) {
                    array_shift($window);
                }
            }
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        fclose($handle);

        return [$window, $total > self::LINES_PER_PAGE];
    }

    /**
     * Return the last LINES_PER_PAGE matching lines from a gzip file.
     *
     * @param string $filePath
     * @param string $query
     * @param bool $caseSensitive
     * @return array{0: array, 1: bool}
     */
    private function searchGzipLast(string $filePath, string $query, bool $caseSensitive): array
    {
        $pattern = $this->buildPattern($query, $caseSensitive);
        $window  = [];
        $total   = 0;

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $handle = gzopen($filePath, 'rb');
        if (!$handle) {
            return [[], false];
        }

        $lineNum = 0;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        while (($line = gzgets($handle)) !== false) {
            $lineNum++;
            if (preg_match($pattern, $line)) {
                $total++;
                $window[] = ['line' => $lineNum, 'content' => rtrim($line, "\r\n")];
                if (count($window) > self::LINES_PER_PAGE) {
                    array_shift($window);
                }
            }
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        gzclose($handle);

        return [$window, $total > self::LINES_PER_PAGE];
    }

    /**
     * Return the last LINES_PER_PAGE matching lines before $beforeLine from a gzip file.
     *
     * @param string $filePath
     * @param string $query
     * @param bool $caseSensitive
     * @param int $beforeLine
     * @return array{0: array, 1: bool}
     */
    private function searchGzipBefore(
        string $filePath,
        string $query,
        bool $caseSensitive,
        int $beforeLine
    ): array {
        $pattern = $this->buildPattern($query, $caseSensitive);
        $window  = [];
        $total   = 0;

        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        $handle = gzopen($filePath, 'rb');
        if (!$handle) {
            return [[], false];
        }

        $lineNum = 0;
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        while (($line = gzgets($handle)) !== false) {
            $lineNum++;
            if ($lineNum >= $beforeLine) {
                break;
            }
            if (preg_match($pattern, $line)) {
                $total++;
                $window[] = ['line' => $lineNum, 'content' => rtrim($line, "\r\n")];
                if (count($window) > self::LINES_PER_PAGE) {
                    array_shift($window);
                }
            }
        }
        // phpcs:ignore Magento2.Functions.DiscouragedFunction
        gzclose($handle);

        return [$window, $total > self::LINES_PER_PAGE];
    }

    /**
     * Remove null bytes and non-printable control characters from the query.
     *
     * Null bytes can interfere with C-level string handling in PHP's regex engine.
     * Stripping them here is a defence-in-depth measure; preg_quote already
     * prevents any regex-injection regardless.
     *
     * @param string $query
     * @return string
     */
    private function sanitizeQuery(string $query): string
    {
        // Strip null bytes and ASCII control characters (except tab \x09)
        return preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/', '', $query) ?? '';
    }

    /**
     * Build a regex pattern for the given query string.
     *
     * @param string $query
     * @param bool $caseSensitive
     * @return string
     */
    private function buildPattern(string $query, bool $caseSensitive): string
    {
        $flags = $caseSensitive ? '' : 'i';
        return '/' . preg_quote($query, '/') . '/' . $flags;
    }

    /**
     * @inheritdoc
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(self::ADMIN_RESOURCE);
    }
}
