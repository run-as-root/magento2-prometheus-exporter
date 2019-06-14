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
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Model\MetricFactory;

class OrderAmountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento2_order_amount';

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var MetricRepositoryInterface
     */
    private $metricRepository;

    /**
     * @var MetricFactory
     */
    private $metricFactory;

    public function __construct(
        MetricRepositoryInterface $metricRepository,
        MetricFactory $metricFactory,
        OrderRepositoryInterface $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->metricRepository = $metricRepository;
        $this->metricFactory = $metricFactory;
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

        try {
            $metric = $this->metricRepository->getByCode(self::METRIC_CODE);
        } catch (NoSuchEntityException $e) {
            /** @var MetricInterface $metric */
            $metric = $this->metricFactory->create();
            $metric->setCode(self::METRIC_CODE);
            $metric->setLabels('');
        }

        $metric->setValue((string)$grandTotal);

        $this->metricRepository->save($metric);

        return true;
    }

}