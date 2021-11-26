<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Index;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\ViewInterface;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class IndexerBacklogCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_indexer_backlog_count_total';

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
        return 'Magento 2 Indexer Backlog Item Count';
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
            $changelog = $view->getChangelog();

            try {
                $value  = $this->getChangelogVersionId($connection, $changelog) -
                    $this->getStateVersionId($connection, $view);

                $this->updateMetricService->update(self::METRIC_CODE, (string)$value, $labels);
            } catch (\Zend_Db_Exception $e) {
                $this->logger->critical($e->getMessage());
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Provide the latest version from changelog table.
     * Avoid service contracts to get the exact data from DB.
     *
     * @param AdapterInterface $adapter
     * @param ChangelogInterface $changelog
     *
     * @return int
     */
    private function getChangelogVersionId(AdapterInterface $adapter, ChangelogInterface $changelog): int
    {
        $select = $adapter->select();
        $select->from($adapter->getTableName($changelog->getName()))
               ->reset(Select::COLUMNS)
               ->order('version_id DESC')
               ->columns(['version_id']);

        return (int)$adapter->fetchOne($select);
    }

    /**
     * Provide the latest version from mview_state table.
     * Avoid service contracts to get the exact data from DB.
     *
     * @param AdapterInterface $adapter
     * @param ViewInterface $view
     *
     * @return int
     */
    private function getStateVersionId(AdapterInterface $adapter, ViewInterface $view): int
    {
        $select = $adapter->select();
        $select->from($adapter->getTableName('mview_state'))->where('view_id = ?', $view->getId())
               ->reset(Select::COLUMNS)
               ->columns(['version_id']);

        return (int)$adapter->fetchOne($select);
    }
}
