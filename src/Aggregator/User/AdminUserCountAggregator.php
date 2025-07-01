<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\User;

use Magento\Framework\App\ResourceConnection;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class AdminUserCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_admin_user_count';

    private $updateMetricService;

    private $connection;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
        ResourceConnection $connection
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->connection          = $connection;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento2 admin user count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $tableName = $this->connection->getConnection()->getTableName('admin_user'); 
        $select = 'SELECT COUNT(user_id) FROM ' . $tableName . ' WHERE is_active = 1';
        $productCount = $this->connection->getConnection()->fetchOne($select);

        return $this->updateMetricService->update(self::METRIC_CODE, $productCount);
    }
}
