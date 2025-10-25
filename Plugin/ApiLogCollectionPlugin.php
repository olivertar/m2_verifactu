<?php
/**
 * Orangecat Verifactuapi ApiLog Collection Plugin
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Plugin;

use Orangecat\Verifactuapi\Model\ResourceModel\ApiLog\Collection;

class ApiLogCollectionPlugin
{
    /**
     * Add invoice data to API log collection
     *
     * @param Collection $subject
     * @return void
     */
    public function beforeLoad(Collection $subject)
    {
        if (!$subject->isLoaded()) {
            $subject->getSelect()->joinLeft(
                ['invoice' => $subject->getTable('sales_invoice')],
                'main_table.invoice_id = invoice.entity_id',
                [
                    'increment_id' => 'invoice.increment_id',
                    'grand_total' => 'invoice.grand_total'
                ]
            );
        }
    }
}
