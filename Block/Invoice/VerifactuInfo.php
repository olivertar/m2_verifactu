<?php
/**
 * Orangecat Verifactuapi Invoice VerifactuInfo Block
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Block\Invoice;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\Registry;
use Orangecat\Verifactuapi\Api\VerifactuInvoiceRepositoryInterface;
use Orangecat\Verifactuapi\Helper\Config;
use Magento\Framework\Exception\NoSuchEntityException;

class VerifactuInfo extends Template
{
    /**
     * @var Registry
     */
    private $registry;
    
    /**
     * @var VerifactuInvoiceRepositoryInterface
     */
    private $verifactuInvoiceRepository;
    
    /**
     * @var Config
     */
    private $configHelper;
    
    /**
     * @param Context $context
     * @param Registry $registry
     * @param VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository
     * @param Config $configHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository,
        Config $configHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->verifactuInvoiceRepository = $verifactuInvoiceRepository;
        $this->configHelper = $configHelper;
    }
    
    /**
     * Get current order
     *
     * @return \Magento\Sales\Model\Order|null
     */
    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }
    
    /**
     * Get invoices from current order
     *
     * @return \Magento\Sales\Model\Order\Invoice[]
     */
    public function getInvoices()
    {
        $order = $this->getOrder();
        return $order ? $order->getInvoiceCollection() : [];
    }
    
    /**
     * Get Verifactu invoice record
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return \Orangecat\Verifactuapi\Model\VerifactuInvoice|null
     */
    public function getVerifactuInvoice($invoice)
    {
        if (!$invoice || !$invoice->getId()) {
            return null;
        }
        
        try {
            return $this->verifactuInvoiceRepository->getByInvoiceId($invoice->getId());
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }
    
    /**
     * Check if module is enabled
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->configHelper->isEnabled();
    }
    
    /**
     * Get Verifactu status
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return string|null
     */
    public function getStatus($invoice)
    {
        $verifactu = $this->getVerifactuInvoice($invoice);
        return $verifactu ? $verifactu->getStatus() : null;
    }
    
    /**
     * Get QR image (base64)
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return string|null
     */
    public function getQrImage($invoice)
    {
        $verifactu = $this->getVerifactuInvoice($invoice);
        return $verifactu ? $verifactu->getQrImage() : null;
    }
    
    /**
     * Get QR URL
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return string|null
     */
    public function getQrUrl($invoice)
    {
        $verifactu = $this->getVerifactuInvoice($invoice);
        return $verifactu ? $verifactu->getQrUrl() : null;
    }
    
    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->configHelper->getQrTitle();
    }
    
    /**
     * Get message based on status
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return string
     */
    public function getMessage($invoice)
    {
        $status = $this->getStatus($invoice);
        
        switch ($status) {
            case 'pending':
            case 'retry':
                return $this->configHelper->getQrMessagePending();
            
            case 'sent':
                return $this->configHelper->getQrMessageSent();
            
            case 'warning':
                return $this->configHelper->getQrMessageWarning();
            
            case 'failed':
                return $this->configHelper->getQrMessageFailed();
            
            default:
                return '';
        }
    }
    
    /**
     * Check if should show QR
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return bool
     */
    public function shouldShowQr($invoice)
    {
        $status = $this->getStatus($invoice);
        return in_array($status, ['confirmed', 'warning']) && ($this->getQrImage($invoice) || $this->getQrUrl($invoice));
    }
    
    /**
     * Check if should show message
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return bool
     */
    public function shouldShowMessage($invoice)
    {
        $status = $this->getStatus($invoice);
        return in_array($status, ['pending', 'retry', 'sent', 'failed', 'warning']);
    }
    
    /**
     * Check if has Verifactu data
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @return bool
     */
    public function hasVerifactuData($invoice)
    {
        return $this->getVerifactuInvoice($invoice) !== null;
    }
}
