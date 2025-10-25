<?php
/**
 * Orangecat Verifactuapi API Client
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Service;

use Magento\Framework\HTTP\Client\Curl;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;

class VerifactuApiClient
{
    const API_BASE_URL = 'https://app.verifactuapi.es/api';
    
    /**
     * @var Curl
     */
    private $curl;
    
    /**
     * @var Json
     */
    private $json;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @var string|null
     */
    private $token;
    
    /**
     * @var string
     */
    private $email;
    
    /**
     * @var string
     */
    private $password;
    
    /**
     * @param Curl $curl
     * @param Json $json
     * @param LoggerInterface $logger
     */
    public function __construct(
        Curl $curl,
        Json $json,
        LoggerInterface $logger
    ) {
        $this->curl = $curl;
        $this->json = $json;
        $this->logger = $logger;
    }
    
    /**
     * Set credentials
     *
     * @param string $email
     * @param string $password
     * @return void
     */
    public function setCredentials($email, $password)
    {
        $this->email = $email;
        $this->password = $password;
    }
    
    /**
     * Login and get token
     *
     * @return bool
     * @throws \Exception
     */
    public function login()
    {
        if (empty($this->email) || empty($this->password)) {
            throw new \Exception('Credentials not set');
        }
        
        $response = $this->post('/login', [
            'email' => $this->email,
            'password' => $this->password
        ], false);
        
        // Check if token exists in response
        if (isset($response['data']['token'])) {
            $this->token = $response['data']['token'];
            return true;
        }
        
        // Alternative response format
        if (isset($response['token'])) {
            $this->token = $response['token'];
            return true;
        }
        
        throw new \Exception('Login failed: No token in response. Message: ' . ($response['message'] ?? 'Unknown error'));
    }
    
    /**
     * Send invoice to Verifactu
     *
     * @param array $data
     * @return array
     * @throws \Exception
     */
    public function sendInvoice($data)
    {
        return $this->post('/alta-registro-facturacion', $data);
    }
    
    /**
     * List invoices
     *
     * @param array $filters
     * @return array
     * @throws \Exception
     */
    public function listInvoices($filters = [])
    {
        return $this->get('/alta-registro-facturacion', $filters);
    }
    
    /**
     * Get invoice by number
     *
     * @param string $numSerie
     * @return array|null
     * @throws \Exception
     */
    public function getInvoiceByNumber($numSerie)
    {
        $response = $this->listInvoices(['NumSerieFactura' => $numSerie]);
        
        if (isset($response['data']['items']) && is_array($response['data']['items'])) {
            foreach ($response['data']['items'] as $item) {
                if (isset($item['NumSerieFactura']) && $item['NumSerieFactura'] === $numSerie) {
                    return $item;
                }
            }
        }
        
        return null;
    }
    
    /**
     * List emisores
     *
     * @return array
     * @throws \Exception
     */
    public function listEmisores()
    {
        return $this->get('/emisor');
    }
    
    /**
     * Get emisor ID by NIF, or create if doesn't exist
     *
     * @param string $nif
     * @param string $nombre
     * @return int
     * @throws \Exception
     */
    public function getOrCreateEmisor($nif, $nombre)
    {
        // List existing emisores
        $response = $this->listEmisores();
        
        // Check if items array exists
        if (isset($response['data']['items']) && is_array($response['data']['items'])) {
            foreach ($response['data']['items'] as $emisor) {
                if (isset($emisor['nif']) && $emisor['nif'] === $nif) {
                    return (int) $emisor['id'];
                }
            }
        }
        $createResponse = $this->post('/emisor', [
            'nif' => $nif,
            'nombre' => $nombre
        ]);
        
        if (isset($createResponse['data']['id'])) {
            return (int) $createResponse['data']['id'];
        }
        
        throw new \Exception('Could not create emisor');
    }
    
    /**
     * Register or update webhook
     *
     * @param string $url
     * @param string $name
     * @param string|null $secret
     * @return array
     * @throws \Exception
     */
    public function registerWebhook($url, $name = 'Magento Webhook', $secret = null)
    {
        // Generate secret if not provided
        if ($secret === null) {
            $secret = bin2hex(random_bytes(32)); // 64 character hex string
        }
        
        // First, check if webhook already exists
        $existing = $this->listWebhooks();
        
        if (isset($existing['data']) && is_array($existing['data'])) {
            foreach ($existing['data'] as $webhook) {
                if (isset($webhook['url']) && $webhook['url'] === $url) {
                    // Webhook already exists with same URL
                    $this->logger->info('Verifactu: Webhook already registered', ['id' => $webhook['id']]);
                    return $webhook;
                }
            }
        }
        
        // Create new webhook
        $payload = [
            'url' => $url,
            'name' => $name,
            'secret_key' => $secret,
            'http_method' => 'POST',
            'enabled' => true
        ];
        
        $this->logger->info('Verifactu: Attempting to register webhook', ['payload' => $payload]);
        
        $response = $this->post('/webhook', $payload);
        
        $this->logger->info('Verifactu: Webhook API response', ['response' => $response]);
        
        // Try different response structures
        if (isset($response['data']['id'])) {
            $this->logger->info('Verifactu: Webhook registered', [
                'id' => $response['data']['id'],
                'url' => $url
            ]);
            // Add secret to response for reference
            $response['data']['secret'] = $secret;
            return $response['data'];
        } elseif (isset($response['data']['items'][0]['id'])) {
            // Alternative response structure
            $webhookData = $response['data']['items'][0];
            $this->logger->info('Verifactu: Webhook registered (items format)', [
                'id' => $webhookData['id'],
                'url' => $url
            ]);
            $webhookData['secret'] = $secret;
            return $webhookData;
        }
        
        $this->logger->error('Verifactu: Could not register webhook - unexpected response structure', [
            'response' => $response
        ]);
        
        throw new \Exception('Could not register webhook - unexpected response structure');
    }
    
    /**
     * List webhooks
     *
     * @return array
     * @throws \Exception
     */
    public function listWebhooks()
    {
        return $this->get('/webhook');
    }
    
    /**
     * Delete webhook
     *
     * @param int $webhookId
     * @return array
     * @throws \Exception
     */
    public function deleteWebhook($webhookId)
    {
        $url = self::API_BASE_URL . '/webhook/' . $webhookId;
        
        $this->curl->setHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json'
        ]);
        
        $this->curl->delete($url);
        
        return $this->handleResponse();
    }
    
    /**
     * Make GET request
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws \Exception
     */
    private function get($endpoint, $params = [])
    {
        if ($this->token === null) {
            $this->login();
        }
        
        $url = self::API_BASE_URL . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }
        
        $this->curl->setHeaders([
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);
        
        $this->curl->get($url);
        
        return $this->handleResponse();
    }
    
    /**
     * Make POST request
     *
     * @param string $endpoint
     * @param array $data
     * @param bool $needsAuth
     * @return array
     * @throws \Exception
     */
    private function post($endpoint, $data, $needsAuth = true)
    {
        if ($needsAuth && $this->token === null) {
            $this->login();
        }
        
        $url = self::API_BASE_URL . $endpoint;
        
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        
        if ($needsAuth) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }
        
        $this->curl->setHeaders($headers);
        $this->curl->post($url, $this->json->serialize($data));
        
        return $this->handleResponse();
    }
    
    /**
     * Handle API response
     *
     * @return array
     * @throws \Exception
     */
    private function handleResponse()
    {
        $status = $this->curl->getStatus();
        $body = $this->curl->getBody();
        
        try {
            $response = $this->json->unserialize($body);
        } catch (\Exception $e) {
            throw new \Exception('Invalid JSON response from API: ' . $body);
        }
        
        if ($status >= 400) {
            $errorMsg = $response['message'] ?? $response['error'] ?? 'Unknown error';
            throw new \Exception('API Error (' . $status . '): ' . $errorMsg);
        }
        
        return $response;
    }
}
