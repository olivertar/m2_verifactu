<?php
/**
 * Orangecat Verifactuapi VerifactuInvoiceRepository
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model;

use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Orangecat\Verifactuapi\Api\VerifactuInvoiceRepositoryInterface;
use Orangecat\Verifactuapi\Model\ResourceModel\VerifactuInvoice as VerifactuInvoiceResource;
use Orangecat\Verifactuapi\Model\VerifactuInvoiceFactory;

class VerifactuInvoiceRepository implements VerifactuInvoiceRepositoryInterface
{
    /**
     * @var VerifactuInvoiceResource
     */
    private $resource;

    /**
     * @var VerifactuInvoiceFactory
     */
    private $verifactuInvoiceFactory;

    /**
     * @var array
     */
    private $instances = [];

    /**
     * @param VerifactuInvoiceResource $resource
     * @param VerifactuInvoiceFactory $verifactuInvoiceFactory
     */
    public function __construct(
        VerifactuInvoiceResource $resource,
        VerifactuInvoiceFactory $verifactuInvoiceFactory
    ) {
        $this->resource = $resource;
        $this->verifactuInvoiceFactory = $verifactuInvoiceFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(VerifactuInvoice $verifactuInvoice)
    {
        try {
            $this->resource->save($verifactuInvoice);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }
        unset($this->instances[$verifactuInvoice->getVerifactuInvoiceId()]);
        return $verifactuInvoice;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($id)
    {
        if (!isset($this->instances[$id])) {
            $verifactuInvoice = $this->verifactuInvoiceFactory->create();
            $this->resource->load($verifactuInvoice, $id);
            if (!$verifactuInvoice->getVerifactuInvoiceId()) {
                throw new NoSuchEntityException(__('Verifactu Invoice with id "%1" does not exist.', $id));
            }
            $this->instances[$id] = $verifactuInvoice;
        }
        return $this->instances[$id];
    }

    /**
     * {@inheritdoc}
     */
    public function getByInvoiceId($invoiceId)
    {
        $verifactuInvoice = $this->verifactuInvoiceFactory->create();
        $this->resource->loadByInvoiceId($verifactuInvoice, $invoiceId);
        
        if (!$verifactuInvoice->getVerifactuInvoiceId()) {
            throw new NoSuchEntityException(
                __('Verifactu Invoice for invoice id "%1" does not exist.', $invoiceId)
            );
        }
        
        return $verifactuInvoice;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(VerifactuInvoice $verifactuInvoice)
    {
        try {
            $verifactuInvoiceId = $verifactuInvoice->getVerifactuInvoiceId();
            $this->resource->delete($verifactuInvoice);
        } catch (\Exception $e) {
            throw new CouldNotDeleteException(__($e->getMessage()));
        }
        unset($this->instances[$verifactuInvoiceId]);
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($id)
    {
        return $this->delete($this->getById($id));
    }
}
