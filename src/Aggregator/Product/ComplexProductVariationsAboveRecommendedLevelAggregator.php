<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Product;

use Magento\Framework\App\ResourceConnection;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class ComplexProductVariationsAboveRecommendedLevelAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_complex_product_variations_above_recommended_level';
    private const RECOMMENDED_VARIATION_LIMIT = 50;

    private UpdateMetricServiceInterface $updateMetricService;
    private ResourceConnection $connection;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
        ResourceConnection $connection
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->connection = $connection;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Number of configurable products with more than 50 variations (above recommended level)';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $connection = $this->connection->getConnection();

        $sql = "
            SELECT COUNT(*) as count
            FROM (
                SELECT parent_id
                FROM {$connection->getTableName('catalog_product_super_link')}
                GROUP BY parent_id
                HAVING COUNT(product_id) > ?
            ) as configurables_above_limit
        ";

        $result = $connection->fetchOne($sql, [self::RECOMMENDED_VARIATION_LIMIT]);
        $configurablesAboveLimit = (int) $result;

        return $this->updateMetricService->update(
            $this->getCode(),
            (string) $configurablesAboveLimit
        );
    }
}
