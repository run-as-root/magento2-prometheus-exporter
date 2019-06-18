<?php

declare(strict_types=1);

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;

class Config
{
    private $config;

    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    public function getMetricsStatus() : array
    {
        return explode(',', $this->config->getValue('metric_configuration/metric/metric_status'));
    }
}
