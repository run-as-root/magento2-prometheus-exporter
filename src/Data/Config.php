<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use RunAsRoot\PrometheusExporter\Model\SourceModel\Metrics as MetricsSource;

class Config
{
    private const CONFIG_PATH_METRICS_ENABLED = 'metric_configuration/metric/metric_status';

    private $config;
    private $metricsSource;

    public function __construct(ScopeConfigInterface $config, MetricsSource $metricsSource)
    {
        $this->config = $config;
        $this->metricsSource = $metricsSource;
    }

    public function getMetricsStatus(): array
    {
        $metrics = $this->config->getValue(self::CONFIG_PATH_METRICS_ENABLED);

        if ($metrics === null) {
            return $this->getDefaultMetrics();
        }

        return explode(',', $metrics);
    }

    public function getDefaultMetrics(): array
    {
        return array_column($this->metricsSource->toOptionArray(), 'value');
    }
}
