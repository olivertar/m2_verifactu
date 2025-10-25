<?php
/**
 * Orangecat Verifactuapi ApiLog Collection
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model\ResourceModel\ApiLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Orangecat\Verifactuapi\Model\ApiLog;
use Orangecat\Verifactuapi\Model\ResourceModel\ApiLog as ApiLogResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'log_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ApiLog::class, ApiLogResource::class);
    }
}
