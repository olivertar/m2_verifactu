<?php
/**
 * Orangecat Verifactuapi Error Message Column
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Ui\Component\Listing\Column;

use Magento\Framework\Escaper;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class ErrorMessage extends Column
{
    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param Escaper $escaper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        Escaper $escaper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->escaper = $escaper;
    }

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
                if (!empty($item['verifactu_error'])) {
                    $error = $this->escaper->escapeHtml($item['verifactu_error']);
                    $shortError = mb_substr($error, 0, 50);
                    
                    if (strlen($error) > 50) {
                        $shortError .= '...';
                    }
                    
                    $item['verifactu_error'] = sprintf(
                        '<span style="color:#f44336; cursor:help;" title="%s">%s</span>',
                        $error,
                        $shortError
                    );
                } else {
                    $item['verifactu_error'] = '<span style="color:#4caf50;">✓ OK</span>';
                }
            }
        }

        return $dataSource;
    }
}
