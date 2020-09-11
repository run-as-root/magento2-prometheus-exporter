<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use RunAsRoot\PrometheusExporter\Model\SourceModel\Metrics as MetricsSource;

class Config
{
    private const CONFIG_PATH_METRICS_ENABLED = 'metric_configuration/metric/metric_status';
    private const CONFIG_PATH_AUTH_TOKEN = 'metric_configuration/security/token';

    private $config;
    private $metricsSource;

    public function __construct(ScopeConfigInterface $config, MetricsSource $metricsSource)
    {
        $this->config = $config;
        $this->metricsSource = $metricsSource;
    }

    public function getMetricsStatus(?string $scopeCode = null): array
    {
        $metrics = $this->config->getValue(self::CONFIG_PATH_METRICS_ENABLED, ScopeInterface::SCOPE_STORE, $scopeCode);

        if ($metrics === null) {
            return $this->getDefaultMetrics();
        }

        return explode(',', $metrics);
    }

    public function getDefaultMetrics(): array
    {
        return array_column($this->metricsSource->toOptionArray(), 'value');
    }

    public function getToken(?string $scopeCode = null): string
    {
        return $this->config->getValue(self::CONFIG_PATH_AUTH_TOKEN, ScopeInterface::SCOPE_STORE, $scopeCode) ?? '';
    }
}
