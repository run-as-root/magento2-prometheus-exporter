<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use RunAsRoot\PrometheusExporter\Aggregator\Cache\CacheFlushCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;
use Throwable;

class IncrementCacheFlushCounterObserver implements ObserverInterface
{
    private UpdateMetricServiceInterface $updateMetricService;

    public function __construct(UpdateMetricServiceInterface $updateMetricService)
    {
        $this->updateMetricService = $updateMetricService;
    }

    public function execute(Observer $observer): void
    {
        try {
            $this->updateMetricService->increment(CacheFlushCountAggregator::METRIC_CODE);
        } catch (Throwable) {
            // Swallow — this event fires during bin/magento setup:install before
            // our db_schema.xml table exists. A metric write must never break the
            // host application.
        }
    }
}
