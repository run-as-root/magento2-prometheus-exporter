<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Order;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order\Item;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory as OrderItemCollectionFactory;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use function array_key_exists;

class OrderItemCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_orders_items_count_total';

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
        return 'Magento2 Order Items Count by state';
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

        $countByStore = [];

        foreach ($ordersAndStores as $orderAndStore) {
            $orderId = $orderAndStore['ORDER_ID'] ?? 0;
            $storeCode = $orderAndStore['STORE_CODE'] ?? '';

            if (!array_key_exists($storeCode, $countByStore)) {
                $countByStore[$storeCode] = [];
            }

            $orderItemCollection = $this->orderItemCollectionFactory->create();
            $searchResults = $orderItemCollection->addFilter('order_id', $orderId);

            foreach ($searchResults->getItems() as $orderItem) {
                /** @var Item $orderItem */
                $status = (string)$orderItem->getStatus();

                if (!array_key_exists($status, $countByStore[$storeCode])) {
                    $countByStore[$storeCode][$status] = 0;
                }

                $countByStore[$storeCode][$status]++;
            }
        }

        foreach ($countByStore as $storeCode => $countByState) {
            foreach ($countByState as $status => $count) {
                $labels = [ 'status' => $status, 'store_code' => $storeCode ];

                $this->updateMetricService->update(self::METRIC_CODE, (string)$count, $labels);
            }
        }

        return true;
    }
}
