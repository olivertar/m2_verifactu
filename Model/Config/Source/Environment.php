<?php
/**
 * Orangecat Verifactuapi Environment Source Model
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface
{
    const ENVIRONMENT_TEST = 'test';
    const ENVIRONMENT_PRODUCTION = 'production';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::ENVIRONMENT_TEST, 'label' => __('Test')],
            ['value' => self::ENVIRONMENT_PRODUCTION, 'label' => __('Production')]
        ];
    }
}
