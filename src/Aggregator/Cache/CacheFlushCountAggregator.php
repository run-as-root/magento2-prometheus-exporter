<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Cache;

use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;

/**
 * Metadata-only aggregator for the cache-flush counter. The actual value is
 * written by {@see \RunAsRoot\PrometheusExporter\Observer\IncrementCacheFlushCounterObserver},
 * which fires on every `clean_cache_by_tags` event. aggregate() is a no-op
 * so the cron collector doesn't overwrite the counter value each minute.
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
        return 'Cumulative count of cache invalidations observed via clean_cache_by_tags.';
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
