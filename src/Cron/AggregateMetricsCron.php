<?php

declare(strict_types=1);

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Cron;

use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;

class AggregateMetricsCron
{
    /**
     * @var MetricAggregatorPool
     */
    private $metricAggregatorPool;

    /**
     * @var Config
     */
    private $config;

    public function __construct(MetricAggregatorPool $metricAggregatorPool, Config $config)
    {
        $this->metricAggregatorPool = $metricAggregatorPool;
        $this->config = $config;
    }

    public function execute(): void
    {
        $enabledMetrics = $this->config->getMetricsStatus();
        foreach ($this->metricAggregatorPool->getItems() as $metricAggregator) {
            if (in_array($metricAggregator->getCode(), $enabledMetrics, true)) {
                $metricAggregator->aggregate();
            }
        }
    }
}
