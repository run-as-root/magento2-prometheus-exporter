<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Order;

use Magento\Framework\App\ResourceConnection;
use Magento\Sales\Model\Order\Item;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class OrderItemCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_orders_items_count_total';

    private UpdateMetricService $updateMetricService;
    private ResourceConnection $resourceConnection;

    public function __construct(
        UpdateMetricService $updateMetricService,
        ResourceConnection $resourceConnection
    ) {
        $this->updateMetricService = $updateMetricService;
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

        $orderItemTable = $connection->getTableName('sales_order_item');
        $salesOrderTable = $connection->getTableName('sales_order');
        $storeTable = $connection->getTableName('store');

        $select = 'SELECT ' . $orderItemTable . '.item_id, ' . $orderItemTable . '.order_id, ' .
            $orderItemTable . '.parent_item_id, ' . $orderItemTable . '.qty_backordered, ' .
            $orderItemTable . '.qty_canceled, ' . $orderItemTable . '.qty_invoiced, ' .
            $orderItemTable . '.qty_ordered, ' . $orderItemTable . '.qty_refunded, ' .
            $orderItemTable . '.qty_shipped, ' . $orderItemTable . '.row_total_incl_tax, ' .
            $storeTable . '.code AS store_code' .
            ' FROM ' . $orderItemTable .
            ' INNER JOIN ' . $salesOrderTable .
            ' ON ' . $orderItemTable . '.order_id = ' . $salesOrderTable . '.entity_id' .
            ' INNER JOIN ' . $storeTable .
            ' ON ' . $storeTable . '.store_id = ' . $salesOrderTable . '.store_id' .
            ' ORDER BY ' . $orderItemTable . '.order_id, ' . $orderItemTable . '.item_id';

        $statement = $connection->query($select);

        $countByStore = [];
        $orderBuffer = [];
        $bufferedOrderId = null;

        while ($row = $statement->fetch()) {
            if (null !== $bufferedOrderId && $row['order_id'] != $bufferedOrderId) {
                $this->countOrderItems($orderBuffer, $countByStore);
                $orderBuffer = [];
            }

            $bufferedOrderId = $row['order_id'];
            $orderBuffer[] = $row;
        }

        if (\count($orderBuffer) > 0) {
            $this->countOrderItems($orderBuffer, $countByStore);
        }

        foreach ($countByStore as $storeCode => $countByState) {
            foreach ($countByState as $status => $count) {
                $labels = ['status' => $status, 'store_code' => $storeCode];

                $this->updateMetricService->update(self::METRIC_CODE, (string) $count, $labels);
            }
        }

        return true;
    }

    /**
     * @param array<int, array<string, mixed>>  $orderItemRows
     * @param array<string, array<string, int>> $countByStore
     */
    private function countOrderItems(array $orderItemRows, array &$countByStore): void
    {
        $childrenQtyBackorderedByParentId = $this->getChildrenQtyBackorderedByParentId($orderItemRows);

        foreach ($orderItemRows as $row) {
            $status = (string) Item::getStatusName((string) $this->getStatusId($row, $childrenQtyBackorderedByParentId));
            $storeCode = (string) ($row['store_code'] ?? '');

            if (!\array_key_exists($storeCode, $countByStore)) {
                $countByStore[$storeCode] = [];
            }

            if (!\array_key_exists($status, $countByStore[$storeCode])) {
                $countByStore[$storeCode][$status] = 0;
            }

            ++$countByStore[$storeCode][$status];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $orderItemRows
     *
     * @return array<int|string, float>
     */
    private function getChildrenQtyBackorderedByParentId(array $orderItemRows): array
    {
        $childrenQtyBackorderedByParentId = [];

        foreach ($orderItemRows as $row) {
            if (null === $row['parent_item_id']) {
                continue;
            }

            if (!\array_key_exists($row['parent_item_id'], $childrenQtyBackorderedByParentId)) {
                $childrenQtyBackorderedByParentId[$row['parent_item_id']] = 0.0;
            }

            $childrenQtyBackorderedByParentId[$row['parent_item_id']] += (float) $row['qty_backordered'];
        }

        return $childrenQtyBackorderedByParentId;
    }

    /**
     * Mirrors Magento\Sales\Model\Order\Item::getStatusId().
     *
     * @param array<string, mixed>     $row
     * @param array<int|string, float> $childrenQtyBackorderedByParentId
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    private function getStatusId(array $row, array $childrenQtyBackorderedByParentId): int
    {
        $backordered = (float) $row['qty_backordered'];

        if (!$backordered && \array_key_exists($row['item_id'], $childrenQtyBackorderedByParentId)) {
            $backordered = $childrenQtyBackorderedByParentId[$row['item_id']];
        }

        $canceled = (float) $row['qty_canceled'];
        $invoiced = (float) $row['qty_invoiced'];
        $ordered = (float) $row['qty_ordered'];
        $refunded = (float) $row['qty_refunded'];
        $shipped = (float) $row['qty_shipped'];

        $actuallyOrdered = $ordered - $canceled - $refunded;

        if (!$invoiced && !$shipped && !$refunded && !$canceled && !$backordered) {
            return Item::STATUS_PENDING;
        }

        if ($shipped && $invoiced && $actuallyOrdered == $shipped) {
            return Item::STATUS_SHIPPED;
        }

        if ($invoiced && !$shipped && $actuallyOrdered == $invoiced) {
            return Item::STATUS_INVOICED;
        }

        if ($backordered && $actuallyOrdered == $backordered) {
            return Item::STATUS_BACKORDERED;
        }

        if ($refunded && $ordered == $refunded) {
            return Item::STATUS_REFUNDED;
        }

        if ($canceled && $ordered == $canceled) {
            return Item::STATUS_CANCELED;
        }

        if (max($shipped, $invoiced) < $actuallyOrdered) {
            return Item::STATUS_PARTIAL;
        }

        return Item::STATUS_MIXED;
    }
}
