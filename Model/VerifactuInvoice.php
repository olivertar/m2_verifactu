<?php
/**
 * Orangecat Verifactuapi VerifactuInvoice Model
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Model;

use Magento\Framework\Model\AbstractModel;

class VerifactuInvoice extends AbstractModel
{
    const STATUS_PENDING = 'pending';
    const STATUS_RETRY = 'retry';
    const STATUS_SENT = 'sent';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_WARNING = 'warning';
    const STATUS_FAILED = 'failed';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Orangecat\Verifactuapi\Model\ResourceModel\VerifactuInvoice::class);
    }

    /**
     * Get Verifactu Invoice ID
     *
     * @return int
     */
    public function getVerifactuInvoiceId()
    {
        return $this->getData('verifactu_invoice_id');
    }

    /**
     * Get Invoice ID
     *
     * @return int
     */
    public function getInvoiceId()
    {
        return $this->getData('invoice_id');
    }

    /**
     * Set Invoice ID
     *
     * @param int $invoiceId
     * @return $this
     */
    public function setInvoiceId($invoiceId)
    {
        return $this->setData('invoice_id', $invoiceId);
    }

    /**
     * Get Status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->getData('status');
    }

    /**
     * Set Status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus($status)
    {
        return $this->setData('status', $status);
    }

    /**
     * Get QR Image (Base64)
     *
     * @return string|null
     */
    public function getQrImage()
    {
        return $this->getData('qr_image');
    }

    /**
     * Set QR Image (Base64)
     *
     * @param string $qr
     * @return $this
     */
    public function setQrImage($qr)
    {
        return $this->setData('qr_image', $qr);
    }

    /**
     * Get QR URL
     *
     * @return string|null
     */
    public function getQrUrl()
    {
        return $this->getData('qr_url');
    }

    /**
     * Set QR URL
     *
     * @param string $url
     * @return $this
     */
    public function setQrUrl($url)
    {
        return $this->setData('qr_url', $url);
    }

    /**
     * Get Identifier
     *
     * @return string|null
     */
    public function getIdentifier()
    {
        return $this->getData('identifier');
    }

    /**
     * Set Identifier
     *
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        return $this->setData('identifier', $identifier);
    }

    /**
     * Get Attempts
     *
     * @return int
     */
    public function getAttempts()
    {
        return (int) $this->getData('attempts');
    }

    /**
     * Set Attempts
     *
     * @param int $attempts
     * @return $this
     */
    public function setAttempts($attempts)
    {
        return $this->setData('attempts', $attempts);
    }

    /**
     * Increment Attempts
     *
     * @return $this
     */
    public function incrementAttempts()
    {
        return $this->setAttempts($this->getAttempts() + 1);
    }

    /**
     * Get Error Message
     *
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->getData('error_message');
    }

    /**
     * Set Error Message
     *
     * @param string|null $message
     * @return $this
     */
    public function setErrorMessage($message)
    {
        return $this->setData('error_message', $message);
    }

    /**
     * Get Last Attempt
     *
     * @return string|null
     */
    public function getLastAttempt()
    {
        return $this->getData('last_attempt');
    }

    /**
     * Set Last Attempt
     *
     * @param string $datetime
     * @return $this
     */
    public function setLastAttempt($datetime)
    {
        return $this->setData('last_attempt', $datetime);
    }

    /**
     * Get Estado AEAT
     *
     * @return string|null
     */
    public function getEstadoAeat()
    {
        return $this->getData('estado_aeat');
    }

    /**
     * Set Estado AEAT
     *
     * @param string|null $estado
     * @return $this
     */
    public function setEstadoAeat($estado)
    {
        return $this->setData('estado_aeat', $estado);
    }

    /**
     * Get Codigo Error AEAT
     *
     * @return string|null
     */
    public function getCodigoErrorAeat()
    {
        return $this->getData('codigo_error_aeat');
    }

    /**
     * Set Codigo Error AEAT
     *
     * @param string|null $codigo
     * @return $this
     */
    public function setCodigoErrorAeat($codigo)
    {
        return $this->setData('codigo_error_aeat', $codigo);
    }

    /**
     * Get Descripcion Error AEAT
     *
     * @return string|null
     */
    public function getDescripcionErrorAeat()
    {
        return $this->getData('descripcion_error_aeat');
    }

    /**
     * Set Descripcion Error AEAT
     *
     * @param string|null $descripcion
     * @return $this
     */
    public function setDescripcionErrorAeat($descripcion)
    {
        return $this->setData('descripcion_error_aeat', $descripcion);
    }
}
