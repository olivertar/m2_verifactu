<?php
/**
 * Orangecat Verifactuapi API Log Data Provider
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Ui\DataProvider;

use Magento\Framework\Api\Filter;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Orangecat\Verifactuapi\Model\ResourceModel\ApiLog\CollectionFactory;

class ApiLogDataProvider extends AbstractDataProvider
{
    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->collection->setOrder('created_at', 'DESC');
    }

    /**
     * Add field filter to collection
     *
     * @param Filter $filter
     * @return void
     */
    public function addFilter(Filter $filter)
    {
        $field = $filter->getField();
        $condition = [$filter->getConditionType() => $filter->getValue()];
        
        // Handle filters for joined fields
        if (in_array($field, ['increment_id', 'grand_total'])) {
            $this->collection->addFieldToFilter('invoice.' . $field, $condition);
        } else {
            $this->collection->addFieldToFilter($field, $condition);
        }
    }
}
