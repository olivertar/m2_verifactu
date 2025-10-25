<?php
/**
 * Orangecat Verifactuapi Invoice Save After Observer
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order\Invoice;
use Orangecat\Verifactuapi\Api\VerifactuInvoiceRepositoryInterface;
use Orangecat\Verifactuapi\Model\VerifactuInvoiceFactory;
use Orangecat\Verifactuapi\Helper\Config;
use Psr\Log\LoggerInterface;

class InvoiceSaveAfter implements ObserverInterface
{
    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var VerifactuInvoiceRepositoryInterface
     */
    private $verifactuInvoiceRepository;

    /**
     * @var VerifactuInvoiceFactory
     */
    private $verifactuInvoiceFactory;

    /**
     * @param Config $configHelper
     * @param LoggerInterface $logger
     * @param VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository
     * @param VerifactuInvoiceFactory $verifactuInvoiceFactory
     */
    public function __construct(
        Config $configHelper,
        LoggerInterface $logger,
        VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository,
        VerifactuInvoiceFactory $verifactuInvoiceFactory
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->verifactuInvoiceRepository = $verifactuInvoiceRepository;
        $this->verifactuInvoiceFactory = $verifactuInvoiceFactory;
    }

    /**
     * Create VerifactuInvoice record for new invoices
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        try {
            // Check if module is enabled
            if (!$this->configHelper->isEnabled()) {
                return;
            }

            /** @var Invoice $invoice */
            $invoice = $observer->getEvent()->getInvoice();

            // Only process if invoice has an ID
            if (!$invoice->getId()) {
                return;
            }

            // Check if Verifactu record already exists
            try {
                $this->verifactuInvoiceRepository->getByInvoiceId($invoice->getId());
                // Record already exists, skip
                $this->logger->debug(
                    'Verifactu: Invoice record already exists, skipping',
                    ['invoice_id' => $invoice->getId()]
                );
                return;
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
                // Record doesn't exist, create it
            }

            // Create new Verifactu invoice record
            $verifactuInvoice = $this->verifactuInvoiceFactory->create();
            $verifactuInvoice->setData([
                'invoice_id' => $invoice->getId(),
                'status' => 'pending',
                'attempts' => 0
            ]);

            $this->verifactuInvoiceRepository->save($verifactuInvoice);
            
            $this->logger->info(
                'Verifactu: Invoice record created as pending',
                ['invoice_id' => $invoice->getId(), 'increment_id' => $invoice->getIncrementId()]
            );
        } catch (\Exception $e) {
            $this->logger->error('Verifactu Observer Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);
        }
    }
}
