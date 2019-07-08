<?php

declare(strict_types=1);

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use RunAsRoot\PrometheusExporter\Model\SourceModel\Metrics as MetricsSource;

class Config
{
    private $config;
    private $metricsSource;

    public function __construct(ScopeConfigInterface $config, MetricsSource $metricsSource)
    {
        $this->config = $config;
        $this->metricsSource = $metricsSource;
    }

    public function getMetricsStatus() : array
    {
        $metrics = $this->config->getValue('metric_configuration/metric/metric_status');

        if ($metrics !== null) {
            $metrics = explode(',', $metrics);
        } else {
            $metrics = $this->getDefaultMetrics();
        }

        return $metrics;
    }

    public function getDefaultMetrics() : array
    {
        return array_column($this->metricsSource->toOptionArray(), 'value');
    }
}
