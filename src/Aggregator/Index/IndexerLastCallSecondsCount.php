<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Index;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Mview\View;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class IndexerLastCallSecondsCount implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_indexer_last_call_seconds_count';

    private UpdateMetricService $updateMetricService;
    private CollectionFactory $indexerCollectionFactory;
    private LoggerInterface $logger;
    private ResourceConnection $resourceConnection;

    /**
     * @param UpdateMetricService $updateMetricService
     * @param CollectionFactory $indexerCollectionFactory
     * @param ResourceConnection $resourceConnection
     * @param LoggerInterface $logger
     */
    public function __construct(
        UpdateMetricService $updateMetricService,
        CollectionFactory $indexerCollectionFactory,
        ResourceConnection $resourceConnection,
        LoggerInterface $logger
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->logger = $logger;
        $this->resourceConnection = $resourceConnection;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Indexer Last Call Seconds Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $result = true;

        $connection = $this->resourceConnection->getConnection();

        foreach ($this->indexerCollectionFactory->create()->getItems() as $index) {
            $labels = [
                'isValid' => $index->isValid(),
                'title' => $index->getTitle(),
                'status' => $index->getStatus(),
            ];

            $view = $index->getView();

            try {
                $value  = $this->getStateLastCall($connection, $view);
                $this->updateMetricService->update(self::METRIC_CODE, (string)$value, $labels);
            } catch (\Zend_Db_Exception $e) {
                $this->logger->critical($e->getMessage());
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Provide amount of seconds since last index update.
     * Avoid service contracts to get the exact data from DB.
     *
     * @param AdapterInterface $adapter
     * @param View $view
     *
     * @return int
     */
    private function getStateLastCall(AdapterInterface $adapter, View $view): int
    {
        $select = $adapter->select();
        $select->from($adapter->getTableName('mview_state'))->where('view_id = ?', $view->getId())
               ->reset(Select::COLUMNS)
               ->columns(['seconds' => 'TIME_TO_SEC(timediff(now(), updated))']);

        return (int)$adapter->fetchOne($select);
    }
}
