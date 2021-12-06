<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Store;

use Magento\Store\Api\StoreRepositoryInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class StoreCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_store_count_total';

    private UpdateMetricService $updateMetricService;

    private StoreRepositoryInterface $storeRepository;

    public function __construct(
        UpdateMetricService $updateMetricService,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->storeRepository     = $storeRepository;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Store Count by status.';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $storeList  = $this->storeRepository->getList();

        $active = 0;
        $disabled = 0;

        foreach ($storeList as $store) {
            $store->getIsActive() ? $active++ : $disabled++;
        }

        $this->updateMetricService->update(self::METRIC_CODE, (string)$active, ['status' => 'enabled']);
        $this->updateMetricService->update(self::METRIC_CODE, (string)$disabled, ['status' => 'disabled']);

        return true;
    }
}
