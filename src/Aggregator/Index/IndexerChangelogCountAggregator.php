<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Index;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Mview\View\Changelog;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class IndexerChangelogCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_indexer_changelog_count_total';

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
        return 'Magento 2 Indexer Changelog Size Count';
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
            /** @var Changelog $changelog */
            $changelog = $view->getChangelog();

            try {
                $value = $this->getChangelogSize($connection, $changelog);
                $this->updateMetricService->update(self::METRIC_CODE, (string)$value, $labels);
            } catch (\Zend_Db_Exception $e) {
                $this->logger->critical($e->getMessage());
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Provide size of changelog table.
     * Avoid service contracts to get the exact data from DB.
     *
     * @param AdapterInterface $adapter
     * @param Changelog $changelog
     *
     * @return int
     */
    private function getChangelogSize(AdapterInterface $adapter, ChangelogInterface $changelog): int
    {
        $select = $adapter->select();
        $select->from($adapter->getTableName($changelog->getName()))
            ->reset(Select::COLUMNS)
            ->columns(['size' => 'count(version_id)']);

        return (int)$adapter->fetchOne($select);
    }
}
