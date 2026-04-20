<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Quote;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class QuotesOverItemLimitCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_quotes_over_item_limit_count_total';
    private const ITEM_LIMIT = 100;

    private UpdateMetricServiceInterface $updateMetricService;
    private ResourceConnection $resourceConnection;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
        ResourceConnection $resourceConnection
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->resourceConnection = $resourceConnection;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Count of active carts with more than 100 items (Magento recommends staying at or below 100).';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $rows = $connection->fetchAll($this->buildSelect($connection));

        foreach ($rows as $row) {
            $count = (int) ($row['QUOTE_COUNT'] ?? 0);
            $storeCode = (string) ($row['STORE_CODE'] ?? '');

            $this->updateMetricService->update(
                self::METRIC_CODE,
                (string) $count,
                ['store_code' => $storeCode]
            );
        }

        return true;
    }

    private function buildSelect(AdapterInterface $connection): Select
    {
        return $connection->select()
            ->from(['q' => $connection->getTableName('quote')])
            ->joinInner(
                ['s' => $connection->getTableName('store')],
                's.store_id = q.store_id',
                []
            )
            ->where('q.is_active = ?', 1)
            ->where('q.items_count > ?', self::ITEM_LIMIT)
            ->reset(Select::COLUMNS)
            ->columns([
                'STORE_CODE' => 's.code',
                'QUOTE_COUNT' => 'COUNT(q.entity_id)',
            ])
            ->group('s.code');
    }
}
