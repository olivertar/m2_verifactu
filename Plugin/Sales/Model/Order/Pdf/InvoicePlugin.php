<?php
/**
 * Orangecat Verifactuapi Invoice PDF Plugin
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Plugin\Sales\Model\Order\Pdf;

use Magento\Sales\Model\Order\Pdf\Invoice as InvoicePdf;
use Orangecat\Verifactuapi\Api\VerifactuInvoiceRepositoryInterface;
use Orangecat\Verifactuapi\Helper\Config;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class InvoicePlugin
{
    /**
     * @var VerifactuInvoiceRepositoryInterface
     */
    private $verifactuInvoiceRepository;
    
    /**
     * @var Config
     */
    private $configHelper;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository
     * @param Config $configHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        VerifactuInvoiceRepositoryInterface $verifactuInvoiceRepository,
        Config $configHelper,
        LoggerInterface $logger
    ) {
        $this->verifactuInvoiceRepository = $verifactuInvoiceRepository;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }
    
    /**
     * Add Verifactu information to PDF
     *
     * @param InvoicePdf $subject
     * @param callable $proceed
     * @param array $invoices
     * @return \Zend_Pdf
     */
    public function aroundGetPdf(InvoicePdf $subject, callable $proceed, $invoices = [])
    {
        // Generate the PDF first
        $result = $proceed($invoices);
        
        if (!$this->configHelper->isEnabled()) {
            return $result;
        }
        
        try {
            $pageIndex = 0;
            foreach ($invoices as $invoice) {
                if (!$invoice || !$invoice->getId()) {
                    $pageIndex++;
                    continue;
                }
                
                try {
                    $verifactuInvoice = $this->verifactuInvoiceRepository->getByInvoiceId($invoice->getId());
                    
                    // Add Verifactu info to the corresponding page
                    if (isset($result->pages[$pageIndex])) {
                        $this->addVerifactuToPdfPage($result->pages[$pageIndex], $verifactuInvoice);
                    }
                } catch (NoSuchEntityException $e) {
                    // No Verifactu data for this invoice, skip
                }
                
                $pageIndex++;
            }
        } catch (\Exception $e) {
            $this->logger->error('Verifactu PDF Plugin Error: ' . $e->getMessage());
        }
        
        return $result;
    }
    
    /**
     * Add Verifactu information to PDF page
     *
     * @param \Zend_Pdf_Page $page
     * @param \Orangecat\Verifactuapi\Model\VerifactuInvoice $verifactuInvoice
     * @return void
     */
    private function addVerifactuToPdfPage($page, $verifactuInvoice)
    {
        $status = $verifactuInvoice->getStatus();
        
        // Set font
        $font = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA);
        $fontBold = \Zend_Pdf_Font::fontWithName(\Zend_Pdf_Font::FONT_HELVETICA_BOLD);
        
        // Starting Y position (bottom of page + margin)
        $y = 100;
        
        // Draw title
        $page->setFont($fontBold, 12);
        $page->drawText($this->configHelper->getQrTitle(), 35, $y, 'UTF-8');
        $y -= 20;
        
        // Check if should show QR
        if (in_array($status, ['confirmed', 'warning'])) {
            $qrImage = $verifactuInvoice->getQrImage();
            $qrUrl = $verifactuInvoice->getQrUrl();
            
            if ($qrImage) {
                try {
                    // Decode base64 QR image
                    $imageData = base64_decode($qrImage);
                    
                    // Create temporary file
                    $tmpFile = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
                    file_put_contents($tmpFile, $imageData);
                    
                    // Load image
                    $image = \Zend_Pdf_Image::imageWithPath($tmpFile);
                    
                    // Draw QR image (80x80 px)
                    $page->drawImage($image, 35, $y - 80, 115, $y);
                    
                    // Clean up temp file
                    @unlink($tmpFile);
                    
                    // Draw URL next to QR
                    if ($qrUrl) {
                        $page->setFont($font, 9);
                        $page->drawText('Verification URL:', 125, $y - 10, 'UTF-8');
                        $page->drawText($qrUrl, 125, $y - 25, 'UTF-8');
                    }
                    
                    // Draw warning message if applicable
                    if ($status === 'warning') {
                        $message = $this->configHelper->getQrMessageWarning();
                        $page->drawText($message, 125, $y - 45, 'UTF-8');
                    }
                } catch (\Exception $e) {
                    $this->logger->error('Failed to add QR to PDF: ' . $e->getMessage());
                }
            } elseif ($qrUrl) {
                // Show URL only if no QR image
                $page->setFont($font, 9);
                $page->drawText('Verification URL: ' . $qrUrl, 35, $y, 'UTF-8');
            }
        } else {
            // Show status message
            $message = $this->getStatusMessage($status);
            if ($message) {
                $page->setFont($font, 9);
                $page->drawText($message, 35, $y, 'UTF-8');
            }
        }
    }
    
    /**
     * Get message based on status
     *
     * @param string $status
     * @return string
     */
    private function getStatusMessage($status)
    {
        switch ($status) {
            case 'pending':
            case 'retry':
                return $this->configHelper->getQrMessagePending();
            
            case 'sent':
                return $this->configHelper->getQrMessageSent();
            
            case 'failed':
                return $this->configHelper->getQrMessageFailed();
            
            default:
                return '';
        }
    }
}
