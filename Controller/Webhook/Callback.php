<?php
/**
 * Orangecat Verifactuapi Webhook Callback Controller
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Orangecat\Verifactuapi\Model\ResourceModel\VerifactuInvoice\CollectionFactory;
use Orangecat\Verifactuapi\Api\VerifactuInvoiceRepositoryInterface;
use Psr\Log\LoggerInterface;

class Callback extends Action implements HttpPostActionInterface, CsrfAwareActionInterface
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    
    /**
     * @var CollectionFactory
     */
    private $verifactuInvoiceCollectionFactory;
    
    /**
     * @var VerifactuInvoiceRepositoryInterface
     */
    private $verifactuInvoiceRepository;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param CollectionFactory $verifactuInvoiceCollectionFactory
     * @param VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CollectionFactory $verifactuInvoiceCollectionFactory,
        VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository,
        LoggerInterface $logger
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->verifactuInvoiceCollectionFactory = $verifactuInvoiceCollectionFactory;
        $this->verifactuInvoiceRepository = $verifactuInvoiceRepository;
        $this->logger = $logger;
    }
    
    /**
     * Execute webhook callback
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            // Get raw POST data
            $rawData = $this->getRequest()->getContent();
            $data = json_decode($rawData, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON payload');
            }
            
            $this->logger->info('Verifactu Webhook received', ['data' => $data]);
            
            // Process the webhook data
            $this->processWebhook($data);
            
            return $result->setData([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Verifactu Webhook error: ' . $e->getMessage());
            
            return $result->setHttpResponseCode(400)->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Process webhook notification
     *
     * @param array $data
     * @return void
     * @throws \Exception
     */
    private function processWebhook($data)
    {
        // Extract invoice identifier from webhook data
        // The structure may vary, adjust according to Verifactu's webhook format
        $numSerieFactura = $data['NumSerieFactura'] ?? $data['data']['NumSerieFactura'] ?? null;
        
        if (!$numSerieFactura) {
            throw new \Exception('NumSerieFactura not found in webhook data');
        }
        
        // Find the verifactu invoice record by identifier
        $collection = $this->verifactuInvoiceCollectionFactory->create();
        $collection->addFieldToFilter('identifier', $numSerieFactura);
        
        $verifactuInvoice = $collection->getFirstItem();
        
        if (!$verifactuInvoice->getId()) {
            $this->logger->warning('Verifactu invoice not found for webhook', [
                'num_serie' => $numSerieFactura
            ]);
            return;
        }
        
        // Extract AEAT status and error information
        $estadoAeat = $data['estado_aeat'] ?? $data['data']['estado_aeat'] ?? null;
        $codigoError = $data['codigo_error_aeat'] ?? $data['data']['codigo_error_aeat'] ?? null;
        $descripcionError = $data['descripcion_error_aeat'] ?? $data['data']['descripcion_error_aeat'] ?? null;
        
        // Check if there's an incidence (error from AEAT)
        $hasIncidencia = isset($data['incidencia']) ? (bool) $data['incidencia'] : false;
        if (isset($data['data']['incidencia'])) {
            $hasIncidencia = (bool) $data['data']['incidencia'];
        }
        
        // Update the record
        $verifactuInvoice->setEstadoAeat($estadoAeat);
        $verifactuInvoice->setCodigoErrorAeat($codigoError);
        $verifactuInvoice->setDescripcionErrorAeat($descripcionError);
        
        // If there's an error or incidencia, update the status
        if ($hasIncidencia || $codigoError || $descripcionError) {
            $verifactuInvoice->setStatus('warning');
            $errorMsg = $descripcionError ?? 'AEAT incidencia detected';
            $verifactuInvoice->setErrorMessage($errorMsg);
        } elseif ($estadoAeat === 'Registrado' || $estadoAeat === 'Correcto') {
            // Successfully registered with AEAT - now confirmed
            $verifactuInvoice->setStatus('confirmed');
            $verifactuInvoice->setErrorMessage(null);
        }
        
        $this->verifactuInvoiceRepository->save($verifactuInvoice);
        
        $this->logger->info('Verifactu invoice updated from webhook', [
            'num_serie' => $numSerieFactura,
            'estado_aeat' => $estadoAeat,
            'has_error' => ($codigoError || $descripcionError) ? true : false
        ]);
    }
    
    /**
     * Create exception in case CSRF validation failed
     *
     * @param RequestInterface $request
     * @return InvalidRequestException|null
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }
    
    /**
     * Perform custom request validation
     *
     * @param RequestInterface $request
     * @return bool|null
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
}
