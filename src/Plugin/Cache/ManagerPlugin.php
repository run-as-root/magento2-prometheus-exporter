<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Plugin\Cache;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\DeploymentConfig;
use RunAsRoot\PrometheusExporter\Aggregator\Cache\CacheFlushCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;
use Throwable;

class ManagerPlugin
{
    private UpdateMetricServiceInterface $updateMetricService;
    private DeploymentConfig $deploymentConfig;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
        DeploymentConfig $deploymentConfig
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->deploymentConfig = $deploymentConfig;
    }

    /**
     * @param string[] $typeCodes
     */
    public function afterFlush(Manager $subject, $result, array $typeCodes = []): mixed
    {
        if (!$this->deploymentConfig->isAvailable()) {
            return $result;
        }

        try {
            $this->updateMetricService->increment(CacheFlushCountAggregator::METRIC_CODE);
        } catch (Throwable) {
            // A metric write must never break the host application.
        }

        return $result;
    }
}
