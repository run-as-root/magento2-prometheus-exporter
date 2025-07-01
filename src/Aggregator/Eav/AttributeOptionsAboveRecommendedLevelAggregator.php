<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Eav;

use Magento\Framework\App\ResourceConnection;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class AttributeOptionsAboveRecommendedLevelAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_eav_attribute_options_above_recommended_level_total';
    private const RECOMMENDED_OPTION_LIMIT = 100;

    private $updateMetricService;
    private $connection;

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
        return 'Number of EAV attributes with more than 100 options (above recommended level)';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $connection = $this->connection->getConnection();

        // Query to count attributes that have more than 100 options
        $sql = "
            SELECT COUNT(*) as count
            FROM (
                SELECT eao.attribute_id
                FROM {$connection->getTableName('eav_attribute_option')} eao
                INNER JOIN {$connection->getTableName('eav_attribute')} ea ON eao.attribute_id = ea.attribute_id
                GROUP BY eao.attribute_id
                HAVING COUNT(eao.option_id) > ?
            ) as attributes_above_limit
        ";

        $result = $connection->fetchOne($sql, [self::RECOMMENDED_OPTION_LIMIT]);
        $attributesAboveLimit = (int) $result;

        return $this->updateMetricService->update(
            $this->getCode(),
            (string) $attributesAboveLimit
        );
    }
}
