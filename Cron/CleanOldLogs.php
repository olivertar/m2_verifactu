<?php
/**
 * Orangecat Verifactuapi Clean Old Logs Cron
 *
 * @category  Orangecat
 * @package   Orangecat_Verifactuapi
 */

namespace Orangecat\Verifactuapi\Cron;

use Orangecat\Verifactuapi\Model\ResourceModel\ApiLog\CollectionFactory;
use Orangecat\Verifactuapi\Helper\Config;
use Psr\Log\LoggerInterface;

class CleanOldLogs
{
    /**
     * @var CollectionFactory
     */
    private $apiLogCollectionFactory;
    
    /**
     * @var Config
     */
    private $configHelper;
    
    /**
     * @var LoggerInterface
     */
    private $logger;
    
    /**
     * @param CollectionFactory $apiLogCollectionFactory
     * @param Config $configHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        CollectionFactory $apiLogCollectionFactory,
        Config $configHelper,
        LoggerInterface $logger
    ) {
        $this->apiLogCollectionFactory = $apiLogCollectionFactory;
        $this->configHelper = $configHelper;
        $this->logger = $logger;
    }
    
    /**
     * Clean old logs
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->configHelper->isEnabled()) {
            return;
        }
        
        $retentionDays = $this->configHelper->getLogRetentionDays();
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        
        try {
            $collection = $this->apiLogCollectionFactory->create();
            $collection->addFieldToFilter('created_at', ['lt' => $cutoffDate]);
            
            $count = $collection->getSize();
            
            if ($count > 0) {
                $collection->walk('delete');
                $this->logger->info("Verifactu: Cleaned {$count} old API logs (older than {$retentionDays} days)");
            }
        } catch (\Exception $e) {
            $this->logger->error('Verifactu: Error cleaning old logs: ' . $e->getMessage());
        }
    }
}
