<?php
/**
 * Orangecat Verifactuapi Status Column
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Ui\Component\Listing\Column;

use Magento\Ui\Component\Listing\Columns\Column;

class VerifactuStatus extends Column
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
                if (isset($item['verifactu_status'])) {
                    $status = $item['verifactu_status'];
                    $label = $this->getStatusLabel($status);
                    $color = $this->getStatusColor($status);
                    
                    $item['verifactu_status'] = sprintf(
                        '<span style="display:inline-block; padding:4px 8px; border-radius:3px; background-color:%s; color:white; font-weight:bold;">%s</span>',
                        $color,
                        __($label)
                    );
                } else {
                    $item['verifactu_status'] = '<span style="color:#999;">—</span>';
                }
            }
        }

        return $dataSource;
    }

    /**
     * Get status label
     *
     * @param string $status
     * @return string
     */
    private function getStatusLabel($status)
    {
        $labels = [
            'pending' => 'Pending',
            'retry' => 'Retry',
            'sent' => 'Sent to Verifactu',
            'confirmed' => 'Confirmed',
            'warning' => 'Warning',
            'failed' => 'Failed'
        ];

        return $labels[$status] ?? ucfirst($status);
    }

    /**
     * Get status color
     *
     * @param string $status
     * @return string
     */
    private function getStatusColor($status)
    {
        $colors = [
            'pending' => '#ff9800',  // Orange
            'retry' => '#ffc107',    // Amber
            'sent' => '#2196f3',     // Blue (awaiting validation)
            'confirmed' => '#4caf50', // Green (validated by AEAT)
            'warning' => '#ff5722',  // Deep Orange (validated but with warnings)
            'failed' => '#f44336'    // Red
        ];

        return $colors[$status] ?? '#9e9e9e';
    }
}
