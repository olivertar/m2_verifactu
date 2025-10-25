<?php
/**
 * Orangecat Verifactuapi VerifactuInvoiceRepository Interface
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Api;

use Orangecat\Verifactuapi\Model\VerifactuInvoice;

interface VerifactuInvoiceRepositoryInterface
{
    /**
     * Save Verifactu Invoice
     *
     * @param VerifactuInvoice $verifactuInvoice
     * @return VerifactuInvoice
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(VerifactuInvoice $verifactuInvoice);

    /**
     * Get by ID
     *
     * @param int $id
     * @return VerifactuInvoice
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById($id);

    /**
     * Get by Invoice ID
     *
     * @param int $invoiceId
     * @return VerifactuInvoice
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByInvoiceId($invoiceId);

    /**
     * Delete
     *
     * @param VerifactuInvoice $verifactuInvoice
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(VerifactuInvoice $verifactuInvoice);

    /**
     * Delete by ID
     *
     * @param int $id
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function deleteById($id);
}
