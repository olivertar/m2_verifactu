<?php
/**
 * Orangecat Verifactuapi Register Webhook Controller
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Controller\Adminhtml\Webhook;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Orangecat\Verifactuapi\Helper\Config;
use Orangecat\Verifactuapi\Service\VerifactuApiClient;

class Register extends Action
{
    /**
     * @var JsonFactory
     */
    private $jsonFactory;
    
    /**
     * @var Config
     */
    private $config;
    
    /**
     * @var VerifactuApiClient
     */
    private $apiClient;
    
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     * @param Config $config
     * @param VerifactuApiClient $apiClient
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        Config $config,
        VerifactuApiClient $apiClient,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->config = $config;
        $this->apiClient = $apiClient;
        $this->storeManager = $storeManager;
    }
    
    /**
     * Register webhook with Verifactu API
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();
        
        try {
            // Get credentials
            $email = $this->config->getApiEmail();
            $password = $this->config->getApiPassword();
            
            if (empty($email) || empty($password)) {
                throw new \Exception('API credentials not configured');
            }
            
            // Set credentials
            $this->apiClient->setCredentials($email, $password);
            
            // Build webhook URL
            $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
            $webhookUrl = rtrim($baseUrl, '/') . '/verifactuapi/webhook/callback';
            
            // Register webhook
            $response = $this->apiClient->registerWebhook($webhookUrl);
            
            return $result->setData([
                'success' => true,
                'message' => __('Webhook registered successfully'),
                'webhook_url' => $webhookUrl,
                'webhook_id' => $response['id'] ?? null,
                'webhook_secret' => $response['secret'] ?? null
            ]);
            
        } catch (\Exception $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if user has permission
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Orangecat_Verifactuapi::config');
    }
}
