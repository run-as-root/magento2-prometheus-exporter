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
use Magento\Sales\Api\OrderRepositoryInterface;
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

    public function __construct(
        UpdateMetricService $updateMetricService,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * @return bool
     * @throws CouldNotSaveException
     */
    public function aggregate(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $orderSearchResult = $this->orderRepository->getList($searchCriteria);

        if ($orderSearchResult->getTotalCount() === 0) {
            return true;
        }

        $orders = $orderSearchResult->getItems();

        $grandTotal = 0.0;
        foreach ($orders as $order) {
            $grandTotal += $order->getGrandTotal();
        }

        $this->updateMetricService->update(self::METRIC_CODE, (string)$grandTotal);

        return true;
    }

}