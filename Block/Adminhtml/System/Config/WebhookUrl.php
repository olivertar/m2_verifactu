<?php
/**
 * Orangecat Verifactuapi Webhook URL Display
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Block\Adminhtml\System\Config;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Store\Model\StoreManagerInterface;

class WebhookUrl extends Field
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    
    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->storeManager = $context->getStoreManager();
    }
    
    /**
     * Render field HTML
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);
        $webhookUrl = rtrim($baseUrl, '/') . '/verifactuapi/webhook/callback';
        
        $html = '<div style="padding:10px;background:#f5f5f5;border:1px solid #ccc;border-radius:3px;">';
        $html .= '<strong style="font-family:monospace;font-size:14px;">' . $this->escapeHtml($webhookUrl) . '</strong>';
        $html .= '</div>';
        
        return $html;
    }
}
