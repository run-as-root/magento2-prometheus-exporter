<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Customer;

use Magento\Framework\App\ResourceConnection;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CustomerCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento2_customer_count_total';

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
        return 'Magento2 Customer Count by state';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $connection = $this->resourceConnection->getConnection();
        $query = 'SELECT ' . 'COUNT(*) AS CUSTOMER_COUNT, STORE.`code` AS STORE_CODE' .
            ' FROM customer_entity AS CUSTOMERS' .
            ' INNER JOIN store AS STORE' .
            ' ON CUSTOMERS.`store_id` = STORE.`store_id`' .
            ' GROUP BY STORE.`store_id`';

        $customersCountPerStore = $connection->fetchAll($query);

        foreach ($customersCountPerStore as $customerCountPerStore) {

            $customerCount = $customerCountPerStore['CUSTOMER_COUNT'] ?? 0;
            $storeCode = $customerCountPerStore['STORE_CODE'] ?? '';

            $labels = [ 'store_code' => $storeCode ];

            $this->updateMetricService->update(self::METRIC_CODE, (string)$customerCount, $labels);
        }
        return true;

    }
}
