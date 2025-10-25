<?php
/**
 * Orangecat Verifactuapi VerifactuInvoice Collection
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model\ResourceModel\VerifactuInvoice;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Orangecat\Verifactuapi\Model\VerifactuInvoice;
use Orangecat\Verifactuapi\Model\ResourceModel\VerifactuInvoice as VerifactuInvoiceResource;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'verifactu_invoice_id';

    /**
     * Initialize resource collection
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            VerifactuInvoice::class,
            VerifactuInvoiceResource::class
        );
    }
}
