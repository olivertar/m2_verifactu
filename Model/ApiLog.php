<?php
/**
 * Orangecat Verifactuapi ApiLog Model
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model;

use Magento\Framework\Model\AbstractModel;

class ApiLog extends AbstractModel
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Orangecat\Verifactuapi\Model\ResourceModel\ApiLog::class);
    }
}
