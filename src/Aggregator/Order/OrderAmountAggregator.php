<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;
use function array_key_exists;

class OrderAmountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_orders_amount_total';

    private MetricRepository $metricRepository;
    private OrderRepositoryInterface $orderRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private StoreRepositoryInterface $storeRepository;
    private UpdateMetricServiceInterface $updateMetricService;

    public function __construct(
        MetricRepository $metricRepository,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreRepositoryInterface $storeRepository,
        UpdateMetricServiceInterface $updateMetricService
    )
    {
        $this->metricRepository = $metricRepository;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeRepository = $storeRepository;
        $this->updateMetricService = $updateMetricService;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento2 Order Amount by state';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $this->resetMetrics();

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $orderSearchResult = $this->orderRepository->getList($searchCriteria);

        if ($orderSearchResult->getTotalCount() === 0) {
            return true;
        }

        $orders = $orderSearchResult->getItems();

        $grandTotalsByStore = [];

        foreach ($orders as $order) {
            $state = $order->getState();
            $storeId = $order->getStoreId();

            try {
                $storeCode = $this->storeRepository->getById($storeId)->getCode();
            } catch (NoSuchEntityException $e) {
                $storeCode = $storeId;
            }

            if (!array_key_exists($storeCode, $grandTotalsByStore)) {
                $grandTotalsByStore[$storeCode] = [];
            }

            if (!array_key_exists($state, $grandTotalsByStore[$storeCode])) {
                $grandTotalsByStore[$storeCode][$state] = 0.0;
            }

            $grandTotalsByStore[$storeCode][$state] += $order->getGrandTotal();
        }

        foreach ($grandTotalsByStore as $storeCode => $grandTotals) {
            foreach ($grandTotals as $state => $grandTotal) {
                $labels = ['state' => $state, 'store_code' => $storeCode];

                $this->updateMetricService->update(self::METRIC_CODE, (string)$grandTotal, $labels);
            }
        }

        return true;
    }

    protected function resetMetrics(): void
    {
        $searchCriteriaMetrics = $this->searchCriteriaBuilder->addFilter('code', self::METRIC_CODE)->create();
        $metricsSearchResult = $this->metricRepository->getList($searchCriteriaMetrics);
        $metrics = $metricsSearchResult->getItems();
        /** @var MetricInterface $metric */
        foreach ($metrics as $metric) {
            $metric->setValue("0");
            $this->metricRepository->save($metric);
        }
    }
}
