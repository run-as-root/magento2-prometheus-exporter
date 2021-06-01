<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use function array_key_exists;

class OrderCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_orders_count_total';

    private $updateMetricService;
    private $orderRepository;
    private $searchCriteriaBuilder;
    private $storeRepository;

    public function __construct(
        UpdateMetricService $updateMetricService,
        OrderRepositoryInterface $orderRepository,
        StoreRepositoryInterface $storeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeRepository = $storeRepository;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento2 Order Count by state';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $orderSearchResult = $this->orderRepository->getList($searchCriteria);

        if ($orderSearchResult->getTotalCount() === 0) {
            return true;
        }

        $orders = $orderSearchResult->getItems();

        $countByStore = [];

        foreach ($orders as $order) {
            $state = $order->getState();
            $storeId = $order->getStoreId();

            try {
                $storeCode = $this->storeRepository->getById($storeId)->getCode();
            } catch (NoSuchEntityException $e) {
                $storeCode = $storeId;
            }

            if (!array_key_exists($storeCode, $countByStore)) {
                $countByStore[$storeCode] = [];
            }

            if (!array_key_exists($state, $countByStore[$storeCode])) {
                $countByStore[$storeCode][$state] = 0;
            }

            $countByStore[$storeCode][$state]++;
        }

        foreach ($countByStore as $storeCode => $countByState) {
            foreach ($countByState as $state => $count) {
                $labels = [ 'state' => $state, 'store_code' => $storeCode ];

                $this->updateMetricService->update(self::METRIC_CODE, (string)$count, $labels);
            }
        }

        return true;
    }
}
