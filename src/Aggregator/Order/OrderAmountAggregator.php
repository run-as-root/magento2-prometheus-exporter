<?php

declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Aggregator\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class OrderAmountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento2_orders_amount_total';

    /**
     * @var UpdateMetricService
     */
    private $updateMetricService;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var StoreRepositoryInterface
     */
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
        return 'Magento2 Order Amount by state';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    /**
     * @return bool
     * @throws CouldNotSaveException
     *
     */
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
            $state = $order->getState();
            $storeId = $order->getStoreId();

            try {
                $store = $this->storeRepository->getById($storeId);
                $storeCode = $store->getCode();
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
}
