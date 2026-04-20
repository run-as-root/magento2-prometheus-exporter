<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Cache;

use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;

/**
 * Metadata-only aggregator for the cache-flush counter. The actual value is
 * incremented by {@see \RunAsRoot\PrometheusExporter\Plugin\Cache\ManagerPlugin}
 * on every `Magento\Framework\App\Cache\Manager::flush()` call. aggregate()
 * is a no-op so the cron collector doesn't overwrite the counter each minute.
 *
 * The aggregator still needs to be registered in the pool so the metric shows
 * up in the admin enable/disable list and in the /metrics render.
 */
class CacheFlushCountAggregator implements MetricAggregatorInterface
{
    public const METRIC_CODE = 'magento_cache_flush_count_total';

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Cumulative count of cache flush invocations (Magento\Framework\App\Cache\Manager::flush).';
    }

    public function getType(): string
    {
        return 'counter';
    }

    public function aggregate(): bool
    {
        return true;
    }
}
