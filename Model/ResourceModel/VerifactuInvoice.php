<?php
/**
 * Orangecat Verifactuapi VerifactuInvoice ResourceModel
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class VerifactuInvoice extends AbstractDb
{
    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('orangecat_verifactu_invoice', 'verifactu_invoice_id');
    }

    /**
     * Load by invoice ID
     *
     * @param \Orangecat\Verifactuapi\Model\VerifactuInvoice $object
     * @param int $invoiceId
     * @return $this
     */
    public function loadByInvoiceId(\Orangecat\Verifactuapi\Model\VerifactuInvoice $object, $invoiceId)
    {
        $connection = $this->getConnection();
        $bind = ['invoice_id' => $invoiceId];
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('invoice_id = :invoice_id');

        $data = $connection->fetchRow($select, $bind);
        if ($data) {
            $object->setData($data);
        }

        $this->_afterLoad($object);

        return $this;
    }
}
