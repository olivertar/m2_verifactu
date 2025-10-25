<?php
/**
 * Orangecat Verifactuapi Service
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Service;

use Magento\Sales\Model\Order\Invoice;
use Orangecat\Verifactuapi\Helper\Config;
use Orangecat\Verifactuapi\Model\ApiLogFactory;
use Orangecat\Verifactuapi\Model\Data\Desglose;
use Orangecat\Verifactuapi\Model\Data\Destinatario;
use Orangecat\Verifactuapi\Model\Data\Emisor;
use Orangecat\Verifactuapi\Model\Data\RegistroFactura;
use Psr\Log\LoggerInterface;

class VerifactuService
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
     * @var ApiLogFactory
     */
    private $apiLogFactory;

    /**
     * @var VerifactuApiClient
     */
    private $apiClient;

    /**
     * @param Config $configHelper
     * @param LoggerInterface $logger
     * @param ApiLogFactory $apiLogFactory
     * @param VerifactuApiClient $apiClient
     */
    public function __construct(
        Config $configHelper,
        LoggerInterface $logger,
        ApiLogFactory $apiLogFactory,
        VerifactuApiClient $apiClient
    ) {
        $this->configHelper = $configHelper;
        $this->logger = $logger;
        $this->apiLogFactory = $apiLogFactory;
        $this->apiClient = $apiClient;
    }

    /**
     * Initialize API client with credentials
     *
     * @throws \Exception
     */
    private function initializeClient()
    {
        $email = $this->configHelper->getApiEmail();
        $password = $this->configHelper->getApiPassword();

        if (empty($email) || empty($password)) {
            throw new \Exception('API credentials not configured');
        }

        $this->apiClient->setCredentials($email, $password);
    }

    /**
     * Send invoice to Verifactu API
     *
     * @param Invoice $invoice
     * @return array
     * @throws \Exception
     */
    public function sendInvoice(Invoice $invoice)
    {
        $this->initializeClient();

        try {
            // Build registro
            $registro = $this->buildRegistroFromInvoice($invoice);
            
            $payload = $registro->toArray();

            // Send to API
            $response = $this->apiClient->sendInvoice($payload);

            // Log success
            if ($this->configHelper->isLogEnabled()) {
                $this->logApiCall(
                    $invoice->getId(),
                    'alta-registro-facturacion',
                    $payload,
                    $response,
                    'success',
                    '200'
                );
            }

            // Get QR from response
            $qrData = $this->extractQrFromResponse($response, $invoice->getIncrementId());

            return [
                'success' => true,
                'data' => $qrData
            ];

        } catch (\Exception $e) {
            // Log error
            if ($this->configHelper->isLogEnabled()) {
                $this->logApiCall(
                    $invoice->getId(),
                    'alta-registro-facturacion',
                    [],
                    ['error' => $e->getMessage()],
                    'error',
                    null,
                    $e->getMessage()
                );
            }

            throw $e;
        }
    }

    /**
     * Build RegistroFactura from Magento Invoice
     *
     * @param Invoice $invoice
     * @return RegistroFactura
     * @throws \Exception
     */
    private function buildRegistroFromInvoice(Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $billingAddress = $order->getBillingAddress();

        // Create emisor
        $emisor = new Emisor(
            $this->configHelper->getEmisorNif(),
            $this->configHelper->getEmisorNombre()
        );

        $registro = new RegistroFactura($emisor);

        // Add destinatario if has NIF
        $customerNif = $order->getCustomerTaxvat();
        if ($customerNif && strlen(trim($customerNif)) > 5) {
            $destinatario = new Destinatario(
                trim($customerNif),
                $billingAddress->getFirstname() . ' ' . $billingAddress->getLastname()
            );
            $registro->setDestinatario($destinatario);
        }

        // Group items by tax rate and create desgloses
        $taxGroups = $this->groupItemsByTaxRate($invoice);

        // Add shipping to appropriate group
        $this->addShippingToTaxGroups($taxGroups, $invoice);

        // Create desglose for each tax rate
        $cuotaTotal = 0;
        $baseImponibleTotal = 0;

        foreach ($taxGroups as $taxRate => $amounts) {
            $baseImponible = round($amounts['base'], 2);
            $cuotaRepercutida = round($baseImponible * $taxRate / 100, 2);

            $desglose = new Desglose($taxRate, $baseImponible, $cuotaRepercutida);
            $registro->addDesglose($desglose);

            $cuotaTotal += $cuotaRepercutida;
            $baseImponibleTotal += $baseImponible;
        }

        // Set invoice details
        $registro->setInvoiceDetails(
            $invoice->getIncrementId(),
            date('Y-m-d', strtotime($invoice->getCreatedAt())),
            $order->getIncrementId()
        );

        // Set totals
        $registro->setTotals(
            round($cuotaTotal, 2),
            round($baseImponibleTotal + $cuotaTotal, 2)
        );

        return $registro;
    }

    /**
     * Add shipping to tax groups
     *
     * @param array $taxGroups
     * @param Invoice $invoice
     * @return void
     */
    private function addShippingToTaxGroups(&$taxGroups, Invoice $invoice)
    {
        $baseShipping = (float) $invoice->getBaseShippingAmount();
        $baseShippingTax = (float) $invoice->getBaseShippingTaxAmount();

        if ($baseShipping > 0) {
            $shippingTaxRate = $baseShipping > 0 && $baseShippingTax > 0
                ? round(($baseShippingTax / $baseShipping) * 100, 2)
                : 0;

            $shippingTaxRate = $this->getNearestValidTaxRate($shippingTaxRate);

            if (!isset($taxGroups[$shippingTaxRate])) {
                $taxGroups[$shippingTaxRate] = ['base' => 0, 'tax' => 0];
            }

            $taxGroups[$shippingTaxRate]['base'] += $baseShipping;
            $taxGroups[$shippingTaxRate]['tax'] += $baseShippingTax;
        }
    }

    /**
     * Extract QR data from API response
     *
     * @param array $response
     * @param string $invoiceNumber
     * @return array
     */
    private function extractQrFromResponse($response, $invoiceNumber)
    {
        // Try to get from response items array
        if (isset($response['data']['items'][0])) {
            $item = $response['data']['items'][0];
            return [
                'qr_image' => $item['qr_image'] ?? null,
                'qr_url' => $item['url_qr'] ?? null,
                'identifier' => $item['NumSerieFactura'] ?? $invoiceNumber
            ];
        }
        
        // Try alternate format (direct data)
        if (isset($response['data']['qr_image'])) {
            $data = $response['data'];
            return [
                'qr_image' => $data['qr_image'],
                'qr_url' => $data['url_qr'] ?? null,
                'identifier' => $data['NumSerieFactura'] ?? $invoiceNumber
            ];
        }

        $this->logger->warning('No QR found in response', ['response' => $response]);
        
        return [
            'qr_image' => null,
            'qr_url' => null,
            'identifier' => $invoiceNumber
        ];
    }

    /**
     * Log API call to database
     *
     * @param int $invoiceId
     * @param string $action
     * @param array|null $requestData
     * @param array|null $responseData
     * @param string $status
     * @param string|null $statusCode
     * @param string|null $errorMessage
     * @return void
     */
    private function logApiCall(
        $invoiceId,
        $action,
        $requestData = null,
        $responseData = null,
        $status = 'pending',
        $statusCode = null,
        $errorMessage = null
    ) {
        try {
            $apiLog = $this->apiLogFactory->create();
            $apiLog->setData([
                'invoice_id' => $invoiceId,
                'action' => $action,
                'request_data' => $requestData ? json_encode($requestData) : null,
                'response_data' => $responseData ? json_encode($responseData) : null,
                'status_code' => $statusCode,
                'status' => $status,
                'error_message' => $errorMessage
            ]);
            $apiLog->save();
        } catch (\Exception $e) {
            $this->logger->error('Failed to log API call: ' . $e->getMessage());
        }
    }

    /**
     * Extract QR data (for backward compatibility)
     *
     * @param array $response
     * @return array
     */
    public function extractQrData($response)
    {
        return $this->extractQrFromResponse($response, null);
    }

    /**
     * Get nearest valid tax rate for Spain
     * Valid rates: 0, 2, 4, 5, 7.5, 10, 21
     *
     * @param float $calculatedRate
     * @return float
     */
    private function getNearestValidTaxRate($calculatedRate)
    {
        $validRates = [0, 2, 4, 5, 7.5, 10, 21];
        
        $nearest = $validRates[0];
        $minDiff = abs($calculatedRate - $nearest);
        
        foreach ($validRates as $rate) {
            $diff = abs($calculatedRate - $rate);
            if ($diff < $minDiff) {
                $minDiff = $diff;
                $nearest = $rate;
            }
        }
        
        return (float) $nearest;
    }

    /**
     * Group invoice items by tax rate
     *
     * @param Invoice $invoice
     * @return array
     */
    private function groupItemsByTaxRate(Invoice $invoice)
    {
        $taxGroups = [];
        
        foreach ($invoice->getAllItems() as $item) {
            // Skip child items (bundled, configurable children)
            if ($item->getOrderItem()->getParentItem()) {
                continue;
            }
            
            $taxPercent = (float) $item->getOrderItem()->getTaxPercent();
            $taxRate = $this->getNearestValidTaxRate($taxPercent);
            
            if (!isset($taxGroups[$taxRate])) {
                $taxGroups[$taxRate] = [
                    'base' => 0,
                    'tax' => 0
                ];
            }
            
            $taxGroups[$taxRate]['base'] += (float) $item->getBaseRowTotal();
            $taxGroups[$taxRate]['tax'] += (float) $item->getBaseTaxAmount();
        }
        
        return $taxGroups;
    }
}
