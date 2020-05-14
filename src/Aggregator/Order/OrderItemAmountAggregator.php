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

class OrderItemAmountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_orders_items_amount_total';

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
        return 'Magento2 Order Items Amount by state';
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

        $grandTotalsByStore = [];

        foreach ($orders as $order) {
            $storeId = $order->getStoreId();

            try {
                $storeCode = $this->storeRepository->getById($storeId)->getCode();
            } catch (NoSuchEntityException $e) {
                $storeCode = $storeId;
            }

            if (!array_key_exists($storeCode, $grandTotalsByStore)) {
                $grandTotalsByStore[$storeCode] = [];
            }

            foreach ($order->getItems() as $orderItem) {
                /** @var OrderItem $orderItem */
                $status = (string)$orderItem->getStatus();

                if (!array_key_exists($status, $grandTotalsByStore[$storeCode])) {
                    $grandTotalsByStore[$storeCode][$status] = 0.0;
                }

                $grandTotalsByStore[$storeCode][$status] += $orderItem->getRowTotalInclTax();
            }
        }

        foreach ($grandTotalsByStore as $storeCode => $grandTotals) {
            foreach ($grandTotals as $status => $grandTotal) {
                $labels = [ 'status' => $status, 'store_code' => $storeCode ];

                $this->updateMetricService->update(self::METRIC_CODE, (string)$grandTotal, $labels);
            }
        }

        return true;
    }
}
