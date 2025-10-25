<?php
/**
 * Orangecat Verifactuapi Invoice Grid Collection Plugin
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Plugin;

use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;

class InvoiceGridCollectionPlugin
{
    /**
     * Add Verifactu data to invoice grid collection
     *
     * @param CollectionFactory $subject
     * @param \Magento\Framework\Data\Collection $collection
     * @param string $requestName
     * @return \Magento\Framework\Data\Collection
     */
    public function afterGetReport(CollectionFactory $subject, $collection, $requestName)
    {
        if ($requestName !== 'sales_order_invoice_grid_data_source') {
            return $collection;
        }

        if ($collection->getMainTable() === $collection->getConnection()->getTableName('sales_invoice_grid')) {
            $verifactuTable = $collection->getConnection()->getTableName('orangecat_verifactu_invoice');
            
            // Check if join already applied
            $fromTables = $collection->getSelect()->getPart(\Zend_Db_Select::FROM);
            if (!isset($fromTables['verifactu'])) {
                $collection->getSelect()->joinLeft(
                    ['verifactu' => $verifactuTable],
                    'main_table.entity_id = verifactu.invoice_id',
                    [
                        'verifactu_status' => 'verifactu.status',
                        'verifactu_identifier' => 'verifactu.identifier',
                        'verifactu_qr' => 'verifactu.qr_image',
                        'verifactu_url_qr' => 'verifactu.qr_url',
                        'verifactu_attempts' => 'verifactu.attempts',
                        'verifactu_error' => 'verifactu.error_message',
                        'verifactu_last_attempt' => 'verifactu.last_attempt'
                    ]
                );
            }
        }
        
        return $collection;
    }
}
