<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Customer;

use Magento\Framework\App\ResourceConnection;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CustomerAddressesCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_customer_addresses_count_total';

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
        return 'Magento2 Customer Addresses count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $connection = $this->resourceConnection->getConnection();

        $query = 'SELECT ' . 'COUNT(*) FROM customer_address_entity';

        $customerAssociatedAddressCount = (int)$connection->fetchOne($query);

        return $this->updateMetricService->update(self::METRIC_CODE, (string)$customerAssociatedAddressCount);
    }
}
