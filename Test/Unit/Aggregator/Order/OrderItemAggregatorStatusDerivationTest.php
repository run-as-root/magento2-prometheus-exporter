<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Order;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Sales\Model\Order\Item;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Order\OrderItemCountAggregator;

/**
 * The order item aggregators derive item status from raw qty columns instead
 * of hydrating Item models. This suite pins that derivation to the framework:
 * every ladder branch of Item::getStatusId(), including children-inherited
 * backorder and the orphan-child edge, must return the identical status id
 * as a real Magento\Sales\Model\Order\Item fed the same values.
 */
final class OrderItemAggregatorStatusDerivationTest extends TestCase
{
    private ObjectManager $objectManager;

    private $getStatusId;

    private $getChildrenMap;

    private object $aggregatorInstance;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);

        $ref = new \ReflectionClass(OrderItemCountAggregator::class);

        $this->getStatusId = $ref->getMethod('getStatusId');
        $this->getStatusId->setAccessible(true);

        $this->getChildrenMap = $ref->getMethod('getChildrenQtyBackorderedByParentId');
        $this->getChildrenMap->setAccessible(true);

        $this->aggregatorInstance = $ref->newInstanceWithoutConstructor();
    }

    private function makeRow(
        int $itemId,
        $parentItemId,
        $qtyBackordered,
        $qtyCanceled,
        $qtyInvoiced,
        $qtyOrdered,
        $qtyRefunded,
        $qtyShipped
    ): array {
        return [
            'item_id' => $itemId,
            'parent_item_id' => $parentItemId,
            'qty_backordered' => $qtyBackordered,
            'qty_canceled' => $qtyCanceled,
            'qty_invoiced' => $qtyInvoiced,
            'qty_ordered' => $qtyOrdered,
            'qty_refunded' => $qtyRefunded,
            'qty_shipped' => $qtyShipped,
        ];
    }

    private function makeRealItem(array $row): Item
    {
        /** @var Item $item */
        $item = $this->objectManager->getObject(Item::class);
        $item->setQtyBackordered($row['qty_backordered']);
        $item->setQtyCanceled($row['qty_canceled']);
        $item->setQtyInvoiced($row['qty_invoiced']);
        $item->setQtyOrdered($row['qty_ordered']);
        $item->setQtyRefunded($row['qty_refunded']);
        $item->setQtyShipped($row['qty_shipped']);

        return $item;
    }

    public function testItMatchesItemGetStatusIdForEveryLadderBranch(): void
    {
        $scenarios = [
            'pending' => [0, 0, 0, null, 0, 0, Item::STATUS_PENDING],
            'pending, zero-qty edge (0 not null)' => [0, 0, 0, 0, 0, 0, Item::STATUS_PENDING],
            'shipped' => [0, 10, 1, 100, 10, 80, Item::STATUS_SHIPPED],
            'shipped, own backordered set but shipped satisfied first' => [1, 10, 1, 100, 10, 80, Item::STATUS_SHIPPED],
            'mixed (shipped branch fails on qty mismatch)' => [1, 10, 1, 100, 10, 99, Item::STATUS_MIXED],
            'invoiced' => [0, 10, 80, 100, 10, 0, Item::STATUS_INVOICED],
            'invoiced, own backordered set but invoiced satisfied first' => [1, 10, 80, 100, 10, 0, Item::STATUS_INVOICED],
            'mixed (invoiced branch fails on qty mismatch)' => [1, 10, 99, 100, 10, 0, Item::STATUS_MIXED],
            'backordered-own' => [80, 10, null, 100, 10, null, Item::STATUS_BACKORDERED],
            'refunded' => [null, null, null, 9, 9, null, Item::STATUS_REFUNDED],
            'canceled' => [null, 9, null, 9, null, null, Item::STATUS_CANCELED],
            'partial' => [1, 10, 70, 100, 10, 79, Item::STATUS_PARTIAL],
            'partial, zero backordered' => [0, 10, 70, 100, 10, 79, Item::STATUS_PARTIAL],
            'float partials (qty_ordered 3, shipped 1.5)' => [0, 0, 0, 3, 0, 1.5, Item::STATUS_PARTIAL],
        ];

        foreach ($scenarios as $name => $scenario) {
            [$qtyBackordered, $qtyCanceled, $qtyInvoiced, $qtyOrdered, $qtyRefunded, $qtyShipped, $expectedStatus] = $scenario;

            $row = $this->makeRow(1, null, $qtyBackordered, $qtyCanceled, $qtyInvoiced, $qtyOrdered, $qtyRefunded, $qtyShipped);

            $actualFromAggregator = $this->getStatusId->invoke($this->aggregatorInstance, $row, []);

            $realItem = $this->makeRealItem($row);
            $actualFromCoreModel = $realItem->getStatusId();

            $this->assertSame($expectedStatus, $actualFromCoreModel, $name . ': fixture disagrees with core Item model');
            $this->assertSame($expectedStatus, $actualFromAggregator, $name . ': derivation disagrees with core Item model');
        }
    }

    public function testItMatchesItemGetStatusIdForChildrenInheritedBackorder(): void
    {
        $parentRow = $this->makeRow(10, null, 0, 0, 0, 5, 0, 0);
        $childRow = $this->makeRow(11, 10, 5, 0, 0, 5, 0, 0);
        $orderBuffer = [$parentRow, $childRow];

        $childrenMap = $this->getChildrenMap->invoke($this->aggregatorInstance, $orderBuffer);

        $actualParentStatus = $this->getStatusId->invoke($this->aggregatorInstance, $parentRow, $childrenMap);
        $actualChildStatus = $this->getStatusId->invoke($this->aggregatorInstance, $childRow, $childrenMap);

        $realParent = $this->makeRealItem($parentRow);
        $realChild = $this->makeRealItem($childRow);
        $realChild->setParentItem($realParent);

        $this->assertTrue($realParent->getHasChildren());
        $this->assertSame(Item::STATUS_BACKORDERED, $realParent->getStatusId(), 'fixture disagrees with core Item model for parent');
        $this->assertSame(Item::STATUS_BACKORDERED, $actualParentStatus, 'derivation disagrees with core Item model for children-inherited backorder (parent)');

        $this->assertSame($realChild->getStatusId(), $actualChildStatus, 'derivation disagrees with core Item model for children-inherited backorder (child, own status unaffected)');
    }

    public function testItMatchesItemGetStatusIdForOrphanChildWithNoInheritance(): void
    {
        $orphanRow = $this->makeRow(20, 999, 3, 0, 0, 3, 0, 0);
        $orderBuffer = [$orphanRow];

        $childrenMap = $this->getChildrenMap->invoke($this->aggregatorInstance, $orderBuffer);
        $this->assertArrayNotHasKey(20, $childrenMap, 'orphan child item_id must never be a key (it is not anyone\'s parent)');

        $actualOrphanStatus = $this->getStatusId->invoke($this->aggregatorInstance, $orphanRow, $childrenMap);

        $realOrphan = $this->makeRealItem($orphanRow);
        $realOrphan->setParentItem(null);

        $this->assertSame(Item::STATUS_BACKORDERED, $realOrphan->getStatusId(), 'fixture disagrees with core Item model for orphan');
        $this->assertSame($realOrphan->getStatusId(), $actualOrphanStatus, 'derivation disagrees with core Item model for orphan child (no inheritance from a deleted parent)');
    }
}
