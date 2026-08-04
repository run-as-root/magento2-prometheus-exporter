<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Cron;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Api\IntervalAwareMetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use function in_array;
use function json_decode;

class AggregateMetricsCron
{
    private const CACHE_KEY_PREFIX = 'run_as_root_prometheus_last_aggregated_';
    private const CACHE_VALUE_ALREADY_RUN = '1';

    private MetricAggregatorPool $metricAggregatorPool;
    private Config $config;
    private MetricRepositoryInterface $metricRepository;
    private LoggerInterface $logger;
    private CacheInterface $cache;

    public function __construct(
        MetricAggregatorPool $metricAggregatorPool,
        Config $config,
        MetricRepositoryInterface $metricRepository,
        LoggerInterface $logger,
        CacheInterface $cache
    ) {
        $this->metricAggregatorPool = $metricAggregatorPool;
        $this->config = $config;
        $this->metricRepository = $metricRepository;
        $this->logger = $logger;
        $this->cache = $cache;
    }

    public function execute(): void
    {
        $enabledMetrics = $this->config->getMetricsStatus();

        foreach ($this->metricAggregatorPool->getItems() as $metricAggregator) {
            if (!in_array($metricAggregator->getCode(), $enabledMetrics, true)) {
                continue;
            }

            try {
                if (!$this->isDue($metricAggregator)) {
                    continue;
                }

                if ($metricAggregator->aggregate()) {
                    $this->markAggregated($metricAggregator);
                }
            } catch (\Exception $e) {
                $msg = sprintf('AggregateMetricsCron: Unable to process jobCode:%s ', $metricAggregator->getCode());
                $this->logger->error($msg . $e->getMessage());
                continue;
            }
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

    private function isDue(MetricAggregatorInterface $metricAggregator): bool
    {
        if (!$metricAggregator instanceof IntervalAwareMetricAggregatorInterface) {
            return true;
        }

        return !$this->cache->load($this->getCacheKey($metricAggregator));
    }

    private function markAggregated(MetricAggregatorInterface $metricAggregator): void
    {
        if (!$metricAggregator instanceof IntervalAwareMetricAggregatorInterface) {
            return;
        }

        $this->cache->save(
            self::CACHE_VALUE_ALREADY_RUN,
            $this->getCacheKey($metricAggregator),
            [],
            $metricAggregator->getMinIntervalInSeconds()
        );
    }

    private function getCacheKey(MetricAggregatorInterface $metricAggregator): string
    {
        return self::CACHE_KEY_PREFIX . $metricAggregator->getCode();
    }
}
