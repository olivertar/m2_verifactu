<?php
/**
 * Orangecat Verifactuapi Log Status Source
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class LogStatus implements OptionSourceInterface
{
    /**
     * Get options
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'success', 'label' => __('Success')],
            ['value' => 'error', 'label' => __('Error')],
            ['value' => 'pending', 'label' => __('Pending')]
        ];
    }
}
