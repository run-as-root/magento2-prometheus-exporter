<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Product;

use Magento\Framework\App\ResourceConnection;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class ProductCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_products_count_total';

    private $updateMetricService;
    private $connection;

    public function __construct(UpdateMetricService $updateMetricService, ResourceConnection $connection)
    {
        $this->updateMetricService = $updateMetricService;
        $this->connection = $connection;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Product Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $tableName = $this->connection->getConnection()->getTableName('catalog_product_entity'); 
        $select = 'SELECT COUNT(entity_id) as ProductCount FROM ' . $tableName;
        $productCount = $this->connection->getConnection()->fetchOne($select);

        return $this->updateMetricService->update(self::METRIC_CODE, $productCount);
    }
}
