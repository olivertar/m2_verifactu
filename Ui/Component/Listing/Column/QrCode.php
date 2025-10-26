<?php
/**
 * Orangecat Verifactuapi QR Code Column
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class QrCode extends Column
{
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                // Use raw status field (not processed by VerifactuStatus column)
                $status = $item['verifactu_status_raw'] ?? null;
                
                // Only show QR if status is 'confirmed' or 'warning'
                if (in_array($status, ['confirmed', 'warning'])) {
                    if (!empty($item['verifactu_qr'])) {
                        $qrData = $item['verifactu_qr'];
                        $uniqueId = 'qr_' . $item['entity_id'];
                        
                        // Show QR as small thumbnail with onclick to show modal
                        $item['verifactu_qr'] = sprintf(
                            '<img src="data:image/png;base64,%s" alt="QR" id="%s" style="width:50px; height:50px; cursor:pointer;" onclick="event.stopPropagation(); showQrModal(this.src)" title="Click to view full size"/>' .
                            '<script>' .
                            'if (!window.showQrModal) {' .
                            '  window.showQrModal = function(src) {' .
                            '    var modal = document.createElement("div");' .
                            '    modal.style.cssText = "position:fixed;top:0;left:0;width:100%%;height:100%%;background:rgba(0,0,0,0.8);z-index:10000;display:flex;align-items:center;justify-content:center;cursor:pointer;";' .
                            '    modal.onclick = function() { document.body.removeChild(modal); };' .
                            '    var img = document.createElement("img");' .
                            '    img.src = src;' .
                            '    img.style.cssText = "max-width:90%%;max-height:90%%;border:5px solid white;border-radius:8px;";' .
                            '    img.onclick = function(e) { e.stopPropagation(); };' .
                            '    modal.appendChild(img);' .
                            '    document.body.appendChild(modal);' .
                            '  };' .
                            '}' .
                            '</script>',
                            $qrData,
                            $uniqueId
                        );
                    } elseif (!empty($item['verifactu_url_qr'])) {
                        // Show link if QR image not available
                        $item['verifactu_qr'] = sprintf(
                            '<a href="%s" target="_blank" onclick="event.stopPropagation()" style="color:#1979c3;">%s</a>',
                            $item['verifactu_url_qr'],
                            __('View QR')
                        );
                    } else {
                        $item['verifactu_qr'] = '<span style="color:#999;">—</span>';
                    }
                } elseif ($status === 'sent') {
                    // Status is 'sent' - awaiting AEAT validation
                    $item['verifactu_qr'] = '<span style="color:#ff9800; font-style:italic;">' . __('Pending AEAT validation') . '</span>';
                } else {
                    // Other statuses (pending, retry, failed) - no QR
                    $item['verifactu_qr'] = '<span style="color:#999;">—</span>';
                }
            }
        }

        return $dataSource;
    }
}
