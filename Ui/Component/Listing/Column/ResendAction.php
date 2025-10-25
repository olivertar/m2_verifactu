<?php
/**
 * Orangecat Verifactuapi Resend Action Column
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Ui\Component\Listing\Column;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;
use Orangecat\Verifactuapi\Helper\Config;

class ResendAction extends Column
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * @var Config
     */
    private $configHelper;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param Config $configHelper
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        Config $configHelper,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->urlBuilder = $urlBuilder;
        $this->configHelper = $configHelper;
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
                // Always show resend button for all invoices
                $item[$this->getData('name')] = [
                    'resend' => [
                        'href' => $this->urlBuilder->getUrl(
                            'verifactuapi/invoice/resend',
                            ['id' => $item['entity_id']]
                        ),
                        'label' => __('Send to Verifactu'),
                        'confirm' => [
                            'title' => __('Send/Resend Invoice'),
                            'message' => __('Are you sure you want to send this invoice to Verifactu? This will reset any previous attempts and force a new submission.')
                        ]
                    ]
                ];
            }
        }

        return $dataSource;
    }
}
