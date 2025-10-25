<?php
/**
 * Orangecat Verifactuapi Resend Invoice Controller
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Controller\Adminhtml\Invoice;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Orangecat\Verifactuapi\Api\VerifactuInvoiceRepositoryInterface;
use Orangecat\Verifactuapi\Model\VerifactuInvoiceFactory;
use Orangecat\Verifactuapi\Helper\Config;
use Orangecat\Verifactuapi\Service\VerifactuService;
use Psr\Log\LoggerInterface;

class Resend extends Action
{
    /**
     * Authorization level
     */
    const ADMIN_RESOURCE = 'Orangecat_Verifactuapi::resend';

    /**
     * @var InvoiceRepositoryInterface
     */
    private $invoiceRepository;

    /**
     * @var VerifactuInvoiceRepositoryInterface
     */
    private $verifactuInvoiceRepository;

    /**
     * @var VerifactuInvoiceFactory
     */
    private $verifactuInvoiceFactory;

    /**
     * @var VerifactuService
     */
    private $verifactuService;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param Context $context
     * @param InvoiceRepositoryInterface $invoiceRepository
     * @param VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository
     * @param VerifactuInvoiceFactory $verifactuInvoiceFactory
     * @param VerifactuService $verifactuService
     * @param Config $configHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        InvoiceRepositoryInterface $invoiceRepository,
        VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository,
        VerifactuInvoiceFactory $verifactuInvoiceFactory,
        VerifactuService $verifactuService,
        Config $configHelper,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->invoiceRepository = $invoiceRepository;
        $this->verifactuInvoiceRepository = $verifactuInvoiceRepository;
        $this->verifactuInvoiceFactory = $verifactuInvoiceFactory;
        $this->verifactuService = $verifactuService;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }

    /**
     * Execute resend action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/invoice/index');

        $invoiceId = $this->getRequest()->getParam('id');
        
        if (!$invoiceId) {
            $this->messageManager->addErrorMessage(__('Invoice ID is required.'));
            return $resultRedirect;
        }

        if (!$this->configHelper->isEnabled()) {
            $this->messageManager->addErrorMessage(__('Verifactu module is disabled.'));
            return $resultRedirect;
        }

        try {
            // Load invoice to verify it exists and get increment_id
            $invoice = $this->invoiceRepository->get($invoiceId);
            
            // Try to load Verifactu invoice record
            try {
                $verifactuInvoice = $this->verifactuInvoiceRepository->getByInvoiceId($invoiceId);
            } catch (NoSuchEntityException $e) {
                // Create a new Verifactu invoice record if it doesn't exist
                $verifactuInvoice = $this->verifactuInvoiceFactory->create();
                $verifactuInvoice->setInvoiceId($invoiceId);
            }
            
            // Force reset for retry - no validations, always allow manual resend
            $verifactuInvoice->setStatus('pending');
            $verifactuInvoice->setAttempts(0);
            $verifactuInvoice->setErrorMessage(null);
            $verifactuInvoice->setLastAttempt(null);
            $verifactuInvoice->setQrImage(null);
            $verifactuInvoice->setQrUrl(null);
            $verifactuInvoice->setEstadoAeat(null);
            $verifactuInvoice->setCodigoErrorAeat(null);
            $verifactuInvoice->setDescripcionErrorAeat(null);
            $this->verifactuInvoiceRepository->save($verifactuInvoice);

            $this->messageManager->addSuccessMessage(
                __('Invoice #%1 has been queued for resending to Verifactu. It will be processed in the next cron run.', 
                $invoice->getIncrementId())
            );

            $this->logger->info('Verifactu: Manual resend initiated for invoice', [
                'invoice_id' => $invoiceId,
                'increment_id' => $invoice->getIncrementId(),
                'admin_user' => $this->_auth->getUser()->getUserName()
            ]);

        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('Invoice not found.'));
            $this->logger->error('Verifactu Resend: Invoice not found', ['invoice_id' => $invoiceId]);
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('An error occurred while resending invoice: %1', $e->getMessage())
            );
            $this->logger->error('Verifactu Resend Error: ' . $e->getMessage(), [
                'invoice_id' => $invoiceId,
                'exception' => $e
            ]);
        }

        return $resultRedirect;
    }
}
