<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use function array_key_exists;
use function count;

class OrderItemAmountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_orders_items_amount_total';

    private UpdateMetricService $updateMetricService;
    private OrderItemCollectionFactory $orderItemCollectionFactory;
    private ResourceConnection $resourceConnection;

    public function __construct(
        UpdateMetricService $updateMetricService,
        OrderItemCollectionFactory $orderItemCollectionFactory,
        ResourceConnection $resourceConnection
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->orderItemCollectionFactory = $orderItemCollectionFactory;
        $this->resourceConnection = $resourceConnection;
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
        $connection = $this->resourceConnection->getConnection();

        $query = 'SELECT ' . 'sales_order.entity_id AS ORDER_ID, store.code AS STORE_CODE' .
            ' FROM sales_order' .
            ' INNER JOIN store' .
            ' ON sales_order.`store_id` = store.store_id';

        $ordersAndStores = $connection->fetchAll($query);

        if (count($ordersAndStores) === 0) {
            return true;
        }

        $grandTotalsByStore = [];

        foreach ($ordersAndStores as $orderAndStore) {
            $orderId = $orderAndStore['ORDER_ID'] ?? 0;
            $storeCode = $orderAndStore['STORE_CODE'] ?? '';

            if (!array_key_exists($storeCode, $grandTotalsByStore)) {
                $grandTotalsByStore[$storeCode] = [];
            }

            $orderItemCollection = $this->orderItemCollectionFactory->create();
            $searchResults = $orderItemCollection->addFilter('order_id', $orderId);

            foreach ($searchResults->getItems() as $orderItem) {
                /** @var Item $orderItem */
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
