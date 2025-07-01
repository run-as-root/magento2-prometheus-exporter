<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Customer;

use Magento\Framework\App\ResourceConnection;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class CustomerGroupCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_customer_group_count_total';

    private UpdateMetricService $updateMetricService;
    private ResourceConnection $resourceConnection;

    public function __construct(UpdateMetricService $updateMetricService, ResourceConnection $resourceConnection)
    {
        $this->updateMetricService   = $updateMetricService;
        $this->resourceConnection = $resourceConnection;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento2 Customer Group Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $connection = $this->resourceConnection->getConnection();

        $tableName = $connection->getTableName('customer_group');
        
        $query = 'SELECT COUNT(*) FROM ' . $tableName;

        $totalGroup = (int)$connection->fetchOne($query);

        return $this->updateMetricService->update(self::METRIC_CODE, (string)$totalGroup);
    }
}
