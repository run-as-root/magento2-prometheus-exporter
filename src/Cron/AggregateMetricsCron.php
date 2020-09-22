<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Cron;

use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use function in_array;
use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;
use function json_decode;

class AggregateMetricsCron
{
    private $metricAggregatorPool;
    private $config;
    private $metricRepository;

    public function __construct(MetricAggregatorPool $metricAggregatorPool, Config $config, MetricRepositoryInterface $metricRepository)
    {
        $this->metricAggregatorPool = $metricAggregatorPool;
        $this->config = $config;
        $this->metricRepository = $metricRepository;
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

    public function executeOnly(string $onlySpecificMetric = ''): string
    {
        $result = '';
        $metricAggregator = $this->metricAggregatorPool->getByCode($onlySpecificMetric);
        $enabledMetrics = $this->config->getMetricsStatus();

        if (!$metricAggregator || !in_array($metricAggregator->getCode(), $enabledMetrics, true)) {
            return $result;
        }

        $metricAggregator->aggregate();
        try {
            $result = json_encode($this->metricRepository->getByCode($onlySpecificMetric)->asArray());
        } catch (NoSuchEntityException $e) {
            $result = '';
        }

        return $result;
    }
}
