<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Observer;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use RunAsRoot\PrometheusExporter\Aggregator\Cache\CacheFlushCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use Throwable;

class IncrementCacheFlushCounterObserver implements ObserverInterface
{
    private UpdateMetricService $updateMetricService;
    private DeploymentConfig $deploymentConfig;

    public function __construct(
        UpdateMetricService $updateMetricService,
        DeploymentConfig $deploymentConfig
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->deploymentConfig = $deploymentConfig;
    }

    public function execute(Observer $observer): void
    {
        if (!$this->deploymentConfig->isAvailable()) {
            // Magento isn't fully installed yet (setup:install in progress).
            // clean_cache_by_tags fires during install for modules we haven't
            // had a chance to create tables for — running a DB write here can
            // crash the process at C level on some PHP/MySQL combos.
            return;
        }

        try {
            $this->updateMetricService->increment(CacheFlushCountAggregator::METRIC_CODE);
        } catch (Throwable) {
            // Swallow — a metric write must never break the host application.
        }
    }
}
