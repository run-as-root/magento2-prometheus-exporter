<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use RunAsRoot\PrometheusExporter\Aggregator\Cache\CacheFlushCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class IncrementCacheFlushCounterObserver implements ObserverInterface
{
    private UpdateMetricServiceInterface $updateMetricService;

    public function __construct(UpdateMetricServiceInterface $updateMetricService)
    {
        $this->updateMetricService = $updateMetricService;
    }

    public function execute(Observer $observer): void
    {
        $this->updateMetricService->increment(CacheFlushCountAggregator::METRIC_CODE);
    }
}
