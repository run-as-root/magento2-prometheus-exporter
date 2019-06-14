<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Cron;

use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;

class AggregateMetricsCron
{
    /**
     * @var MetricAggregatorPool
     */
    private $metricAggregatorPool;

    public function __construct(MetricAggregatorPool $metricAggregatorPool)
    {
        $this->metricAggregatorPool = $metricAggregatorPool;
    }

    public function execute(): void
    {
        foreach ($this->metricAggregatorPool->getItems() as $metricAggregator) {
            $metricAggregator->aggregate();
        }
    }
}