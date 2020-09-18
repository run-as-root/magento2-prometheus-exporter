<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Store;

use Magento\Store\Api\StoreRepositoryInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class StoreCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_store_count_total';

    private $updateMetricService;

    private $storeRepository;

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
        return 'Magento 2 Store Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $storeList  = $this->storeRepository->getList();
        $storeCount = (string)count($storeList);

        return $this->updateMetricService->update(self::METRIC_CODE, $storeCount);
    }
}
