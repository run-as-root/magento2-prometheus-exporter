<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Cron;

use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;
use function in_array;

class AggregateMetricsCron
{
    private $metricAggregatorPool;
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
            if (!in_array($metricAggregator->getCode(), $enabledMetrics, true)) {
                continue;
            }

            $metricAggregator->aggregate();
        }
    }
}
