<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Order;

use Magento\Framework\App\ResourceConnection;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Order\OrderItemAmountAggregator;
use RunAsRoot\PrometheusExporter\Aggregator\Order\OrderItemCountAggregator;
use RunAsRoot\PrometheusExporter\Api\IntervalAwareMetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

/**
 * OrderItemAmountAggregator/OrderItemCountAggregator run a single streamed query, but it is
 * still an unbounded full scan over sales_order_item - this pins the throttle contract added on
 * top of the N+1 fix in #66/#69, since the query-shape/derivation behavior is already covered by
 * OrderItemAggregatorStatusDerivationTest.
 */
final class OrderItemThrottleTest extends TestCase
{
    public function test_order_item_amount_aggregator_is_interval_aware_and_throttles_to_five_minutes(): void
    {
        $sut = new OrderItemAmountAggregator(
            $this->createMock(UpdateMetricService::class),
            $this->createMock(ResourceConnection::class)
        );

        self::assertInstanceOf(IntervalAwareMetricAggregatorInterface::class, $sut);
        self::assertSame(300, $sut->getMinIntervalInSeconds());
    }

    public function test_order_item_count_aggregator_is_interval_aware_and_throttles_to_five_minutes(): void
    {
        $sut = new OrderItemCountAggregator(
            $this->createMock(UpdateMetricService::class),
            $this->createMock(ResourceConnection::class)
        );

        self::assertInstanceOf(IntervalAwareMetricAggregatorInterface::class, $sut);
        self::assertSame(300, $sut->getMinIntervalInSeconds());
    }
}
