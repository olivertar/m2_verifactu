<?php
/**
 * Orangecat Verifactuapi Config Helper
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    const XML_PATH_ENABLED = 'verifactuapi/general/enabled';
    const XML_PATH_ENVIRONMENT = 'verifactuapi/general/environment';
    const XML_PATH_API_EMAIL = 'verifactuapi/api/email';
    const XML_PATH_API_PASSWORD = 'verifactuapi/api/password';
    const XML_PATH_EMISOR_NIF = 'verifactuapi/emisor/nif';
    const XML_PATH_EMISOR_NOMBRE = 'verifactuapi/emisor/nombre';
    const XML_PATH_EMISOR_CP = 'verifactuapi/emisor/cp';
    const XML_PATH_MAX_ATTEMPTS = 'verifactuapi/retry/max_attempts';
    const XML_PATH_RETRY_INTERVAL = 'verifactuapi/retry/retry_interval';
    const XML_PATH_NOTIFICATION_ENABLED = 'verifactuapi/notification/enabled';
    const XML_PATH_EMAIL_RECIPIENTS = 'verifactuapi/notification/email_recipients';
    const XML_PATH_EMAIL_SENDER = 'verifactuapi/notification/email_sender';
    const XML_PATH_LOG_ENABLED = 'verifactuapi/debug/log_enabled';
    const XML_PATH_LOG_RETENTION_DAYS = 'verifactuapi/debug/log_retention_days';
    const XML_PATH_QR_TITLE = 'verifactuapi/qr_display/title';
    const XML_PATH_QR_MESSAGE_PENDING = 'verifactuapi/qr_display/message_pending';
    const XML_PATH_QR_MESSAGE_SENT = 'verifactuapi/qr_display/message_sent';
    const XML_PATH_QR_MESSAGE_WARNING = 'verifactuapi/qr_display/message_warning';
    const XML_PATH_QR_MESSAGE_FAILED = 'verifactuapi/qr_display/message_failed';

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getEnvironment($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_ENVIRONMENT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getApiEmail($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_API_EMAIL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getApiPassword($storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_API_PASSWORD,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ? $this->encryptor->decrypt($value) : '';
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getEmisorNif($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMISOR_NIF,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getEmisorNombre($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMISOR_NOMBRE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getEmisorCp($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMISOR_CP,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getMaxAttempts($storeId = null)
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_MAX_ATTEMPTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getRetryInterval($storeId = null)
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_RETRY_INTERVAL,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isNotificationEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_NOTIFICATION_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return array
     */
    public function getEmailRecipients($storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_RECIPIENTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ? array_map('trim', explode(',', $value)) : [];
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getEmailSender($storeId = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_EMAIL_SENDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return bool
     */
    public function isLogEnabled($storeId = null)
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_LOG_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getQrTitle($storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_QR_TITLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ?: 'VERIFACTU - Factura verificable en la sede electrónica de la AEAT';
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getQrMessagePending($storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_QR_MESSAGE_PENDING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ?: 'Esta factura está pendiente de ser enviada a la AEAT para su verificación. El código QR estará disponible una vez completado el proceso.';
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getQrMessageSent($storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_QR_MESSAGE_SENT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ?: 'Esta factura aún no ha sido confirmada por la AEAT. Cuando sea confirmada, en este lugar usted verá el código QR de verificación.';
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getQrMessageWarning($storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_QR_MESSAGE_WARNING,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ?: 'Esta factura ha sido registrada con advertencias. Puede verificarla usando el código QR o el enlace proporcionado.';
    }

    /**
     * @param int|null $storeId
     * @return string
     */
    public function getQrMessageFailed($storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_QR_MESSAGE_FAILED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value ?: 'Ha habido un error con la validación de esta factura. Cuando esté solucionado, aquí verá el código QR de verificación.';
    }

    /**
     * @param int|null $storeId
     * @return int
     */
    public function getLogRetentionDays($storeId = null)
    {
        $value = (int) $this->scopeConfig->getValue(
            self::XML_PATH_LOG_RETENTION_DAYS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        return $value > 0 ? $value : 30; // Default 30 days
    }
}
