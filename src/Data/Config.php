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
    /**
     * @var StoreConfigInterface
     */
    private $config;

    public function __construct(ScopeConfigInterface $config)
    {
        $this->config = $config;
    }

    public function isCustomerMetricsEnabled() : bool
    {
        return (bool) $this->config->getValue('metric_configuration/metric/customer');
    }

    public function isOrdersMetricsEnabled() : bool
    {
        return (bool) $this->config->getValue('metric_configuration/metric/order');
    }
}
