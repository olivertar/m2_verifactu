<?php
/**
 * Orangecat Verifactuapi Process Pending Invoices Cron
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Cron;

use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Orangecat\Verifactuapi\Api\VerifactuInvoiceRepositoryInterface;
use Orangecat\Verifactuapi\Model\ResourceModel\VerifactuInvoice\CollectionFactory as VerifactuInvoiceCollectionFactory;
use Orangecat\Verifactuapi\Helper\Config;
use Orangecat\Verifactuapi\Service\VerifactuService;
use Psr\Log\LoggerInterface;

class ProcessPendingInvoices
{
    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var VerifactuService
     */
    private $verifactuService;

    /**
     * @var VerifactuInvoiceCollectionFactory
     */
    private $verifactuInvoiceCollectionFactory;

    /**
     * @var VerifactuInvoiceRepositoryInterface
     */
    private $verifactuInvoiceRepository;

    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var TransportBuilder
     */
    private $transportBuilder;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @param Config $configHelper
     * @param VerifactuService $verifactuService
     * @param VerifactuInvoiceCollectionFactory $verifactuInvoiceCollectionFactory
     * @param VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository
     * @param InvoiceRepository $invoiceRepository
     * @param LoggerInterface $logger
     * @param DateTime $dateTime
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Config $configHelper,
        VerifactuService $verifactuService,
        VerifactuInvoiceCollectionFactory $verifactuInvoiceCollectionFactory,
        VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository,
        InvoiceRepository $invoiceRepository,
        LoggerInterface $logger,
        DateTime $dateTime,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->configHelper = $configHelper;
        $this->verifactuService = $verifactuService;
        $this->verifactuInvoiceCollectionFactory = $verifactuInvoiceCollectionFactory;
        $this->verifactuInvoiceRepository = $verifactuInvoiceRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->logger = $logger;
        $this->dateTime = $dateTime;
        $this->transportBuilder = $transportBuilder;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->configHelper->isEnabled()) {
            return;
        }

        $this->logger->info('Verifactu Cron: Starting processing pending invoices');

        $maxAttempts = $this->configHelper->getMaxAttempts();
        $retryInterval = $this->configHelper->getRetryInterval(); // minutes

        // Get pending and retry Verifactu invoices
        $verifactuCollection = $this->verifactuInvoiceCollectionFactory->create();
        $verifactuCollection->addFieldToFilter('status', ['in' => ['pending', 'retry']])
                           ->addFieldToFilter('attempts', ['lt' => $maxAttempts]);

        // Add filter for retry interval
        $retryThreshold = date('Y-m-d H:i:s', $this->dateTime->gmtTimestamp() - ($retryInterval * 60));
        $verifactuCollection->getSelect()->where(
            'last_attempt IS NULL OR last_attempt < ?',
            $retryThreshold
        );
        
        // Limit to 50 invoices per cron run to avoid timeouts
        $verifactuCollection->setPageSize(50);
        $verifactuCollection->setCurPage(1);

        $this->logger->info('Verifactu Cron: Found ' . $verifactuCollection->getSize() . ' invoices to process (processing up to 50)');

        foreach ($verifactuCollection as $verifactuInvoice) {
            $this->processInvoice($verifactuInvoice);
        }

        $this->logger->info('Verifactu Cron: Finished processing');
    }

    /**
     * Process individual invoice
     *
     * @param \Orangecat\Verifactuapi\Model\VerifactuInvoice $verifactuInvoice
     * @return void
     */
    private function processInvoice($verifactuInvoice)
    {
        $invoiceId = $verifactuInvoice->getInvoiceId();
        
        try {
            // Load Magento invoice
            $invoice = $this->invoiceRepository->get($invoiceId);
            $incrementId = $invoice->getIncrementId();

            $this->logger->info("Verifactu: Processing invoice #{$incrementId}", [
                'invoice_id' => $invoiceId,
                'attempt' => $verifactuInvoice->getAttempts() + 1
            ]);

            // Send to Verifactu
            $response = $this->verifactuService->sendInvoice($invoice);

            // Check response
            if (!empty($response['success'])) {
                // Success - response already contains extracted QR data
                $qrData = $response['data'];
                
                // Set status to 'sent' - waiting for AEAT confirmation via webhook
                $verifactuInvoice->setStatus('sent');
                $verifactuInvoice->setQrImage($qrData['qr_image']);
                $verifactuInvoice->setQrUrl($qrData['qr_url']);
                $verifactuInvoice->setIdentifier($qrData['identifier']);
                $verifactuInvoice->setErrorMessage(null);
                $verifactuInvoice->setLastAttempt(date('Y-m-d H:i:s'));
                $this->verifactuInvoiceRepository->save($verifactuInvoice);

                $this->logger->info("Verifactu: Invoice #{$incrementId} sent to Verifactu, awaiting AEAT validation");
            } else {
                // API returned error
                $errorMsg = $response['message'] ?? 'Unknown error from API';
                $this->handleFailure($verifactuInvoice, $invoice, $errorMsg);
            }

        } catch (\Exception $e) {
            // Exception occurred
            try {
                $invoice = $this->invoiceRepository->get($invoiceId);
                $this->handleFailure($verifactuInvoice, $invoice, $e->getMessage());
            } catch (\Exception $ex) {
                $this->logger->error('Verifactu: Failed to load invoice for error handling: ' . $ex->getMessage());
            }
        }
    }

    /**
     * Handle invoice processing failure
     *
     * @param \Orangecat\Verifactuapi\Model\VerifactuInvoice $verifactuInvoice
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param string $errorMessage
     * @return void
     */
    private function handleFailure($verifactuInvoice, $invoice, $errorMessage)
    {
        $invoiceId = $invoice->getId();
        $incrementId = $invoice->getIncrementId();
        $currentAttempts = (int) $verifactuInvoice->getAttempts();
        $newAttempts = $currentAttempts + 1;
        $maxAttempts = $this->configHelper->getMaxAttempts();

        $this->logger->error("Verifactu: Failed to process invoice #{$incrementId}: {$errorMessage}", [
            'invoice_id' => $invoiceId,
            'attempt' => $newAttempts,
            'max_attempts' => $maxAttempts
        ]);

        $verifactuInvoice->setAttempts($newAttempts);
        $verifactuInvoice->setErrorMessage(substr($errorMessage, 0, 1000)); // Limit error length
        $verifactuInvoice->setLastAttempt(date('Y-m-d H:i:s'));

        if ($newAttempts >= $maxAttempts) {
            // Max attempts reached
            $verifactuInvoice->setStatus('failed');
            $this->verifactuInvoiceRepository->save($verifactuInvoice);

            $this->logger->error("Verifactu: Invoice #{$incrementId} marked as failed after {$newAttempts} attempts");

            // Send notification
            $this->sendFailureNotification($invoice, $errorMessage);

        } else {
            // Still have retries left
            $verifactuInvoice->setStatus('retry');
            $this->verifactuInvoiceRepository->save($verifactuInvoice);

            $this->logger->info("Verifactu: Invoice #{$incrementId} will be retried. Attempt {$newAttempts} of {$maxAttempts}");
        }
    }

    /**
     * Send failure notification email
     *
     * @param \Magento\Sales\Model\Order\Invoice $invoice
     * @param string $errorMessage
     * @return void
     */
    private function sendFailureNotification($invoice, $errorMessage)
    {
        if (!$this->configHelper->isNotificationEnabled()) {
            return;
        }

        $recipients = $this->configHelper->getEmailRecipients();
        if (empty($recipients)) {
            $this->logger->warning('Verifactu: No email recipients configured for notifications');
            return;
        }

        try {
            $store = $this->storeManager->getStore($invoice->getStoreId());
            $sender = $this->configHelper->getEmailSender();
            
            $templateVars = [
                'invoice_increment_id' => $invoice->getIncrementId(),
                'invoice_id' => $invoice->getId(),
                'order_increment_id' => $invoice->getOrder()->getIncrementId(),
                'error_message' => $errorMessage,
                'attempts' => $invoice->getData('verifactu_attempts'),
                'store_name' => $store->getName()
            ];

            foreach ($recipients as $recipient) {
                $transport = $this->transportBuilder
                    ->setTemplateIdentifier('verifactuapi_failure_notification') // You'll need to create this email template
                    ->setTemplateOptions([
                        'area' => Area::AREA_ADMINHTML,
                        'store' => $store->getId()
                    ])
                    ->setTemplateVars($templateVars)
                    ->setFromByScope($sender, $store->getId())
                    ->addTo($recipient)
                    ->getTransport();

                $transport->sendMessage();
            }

            $this->logger->info("Verifactu: Failure notification sent for invoice #{$invoice->getIncrementId()}");

        } catch (\Exception $e) {
            $this->logger->error('Verifactu: Failed to send notification email: ' . $e->getMessage());
        }
    }
}
