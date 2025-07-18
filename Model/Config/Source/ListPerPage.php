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

namespace Mageprince\LogViewer\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class ListPerPage implements ArrayInterface
{
    /**
     * Retrieve list per page count
     *
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 5, 'label' => '5'],
            ['value' => 10, 'label' => '10'],
            ['value' => 25, 'label' => '25'],
            ['value' => 50, 'label' => '50'],
            ['value' => 100, 'label' => '100']
        ];
    }
}
