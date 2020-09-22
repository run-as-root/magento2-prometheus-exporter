<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Payment;

use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class ActivePaymentMethodsCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_active_payment_methods_count_total';

    private $updateMetricService;

    private $storeRepository;

    private $paymentMethodList;

    public function __construct(
        UpdateMetricService $updateMetricService,
        StoreRepositoryInterface $storeRepository,
        PaymentMethodListInterface $paymentMethodList
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->storeRepository     = $storeRepository;
        $this->paymentMethodList   = $paymentMethodList;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 active Payment Methods Count by store';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $storeList = $this->storeRepository->getList();

        foreach ($storeList as $store) {
            $storeId = $store->getId();

            $activePaymentMethods      = $this->paymentMethodList->getActiveList($storeId);
            $activePaymentMethodsCount = (string)count($activePaymentMethods);

            $labels = ['store_id' => $storeId];

            $this->updateMetricService->update(self::METRIC_CODE, $activePaymentMethodsCount, $labels);
        }

        return true;
    }
}
