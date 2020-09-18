<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Shipping;

use Magento\Shipping\Model\Config\Source\Allmethods as ShippingAllMethods;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class ActiveShippingMethodsCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_active_shipping_methods_count_total';

    private $updateMetricService;

    private $shippingMethods;

    public function __construct(UpdateMetricService $updateMetricService, ShippingAllMethods $shippingMethods)
    {
        $this->updateMetricService = $updateMetricService;
        $this->shippingMethods     = $shippingMethods;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 active Shipping Methods count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $shippingMethods      = $this->shippingMethods->toOptionArray(true);
        $shippingMethodsCount = (string)count($shippingMethods);

        return $this->updateMetricService->update(self::METRIC_CODE, $shippingMethodsCount);
    }
}
