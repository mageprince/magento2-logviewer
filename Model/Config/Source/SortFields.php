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

namespace Mageprince\LogViewer\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class SortFields implements OptionSourceInterface
{
    /**
     * Retrieve sortable columns
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'name', 'label' => __('File Name')],
            ['value' => 'mod_time', 'label' => __('Last Modified')]
        ];
    }
}
