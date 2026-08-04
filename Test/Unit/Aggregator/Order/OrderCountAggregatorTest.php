<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Order\OrderCountAggregator;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\IntervalAwareMetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class OrderCountAggregatorTest extends TestCase
{
    private const METRIC_CODE = 'magento_orders_count_total';

    private OrderCountAggregator $subject;

    /** @var MockObject|MetricRepository */
    private $metricRepository;

    /** @var MockObject|SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    protected function setUp(): void
    {
        $this->metricRepository = $this->createMock(MetricRepository::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);

        $this->subject = new OrderCountAggregator(
            $this->metricRepository,
            $this->searchCriteriaBuilder,
            $this->updateMetricService,
            $this->resourceConnection
        );
    }

    private function mockResetMetrics(): void
    {
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilder->method('addFilter')
            ->with('code', self::METRIC_CODE)
            ->willReturnSelf();
        $this->searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $metric = $this->createMock(MetricInterface::class);
        $metric->expects($this->once())->method('setValue')->with('0');

        $searchResults = $this->createMock(SearchResultsInterface::class);
        $searchResults->method('getItems')->willReturn([$metric]);

        $this->metricRepository->method('getList')->with($searchCriteria)->willReturn($searchResults);
        $this->metricRepository->expects($this->once())->method('save')->with($metric);
    }

    public function test_it_exposes_metric_metadata(): void
    {
        self::assertSame(self::METRIC_CODE, $this->subject->getCode());
        self::assertSame('gauge', $this->subject->getType());
        self::assertSame('Magento2 Order Count by state', $this->subject->getHelp());
    }

    public function test_it_throttles_to_five_minutes(): void
    {
        self::assertInstanceOf(IntervalAwareMetricAggregatorInterface::class, $this->subject);
        self::assertSame(300, $this->subject->getMinIntervalInSeconds());
    }

    public function test_it_resets_and_updates_order_count_by_state_and_store(): void
    {
        $this->mockResetMetrics();

        $connection = $this->createMock(AdapterInterface::class);
        $this->resourceConnection->method('getConnection')->willReturn($connection);
        $connection->method('getTableName')->willReturnArgument(0);

        $rows = [
            ['ORDER_COUNT' => '12', 'ORDER_STATE' => 'complete', 'STORE_CODE' => 'default'],
            ['ORDER_COUNT' => '3', 'ORDER_STATE' => 'processing', 'STORE_CODE' => 'eu'],
        ];
        $connection->expects($this->once())->method('fetchAll')->willReturn($rows);

        $expected = [
            [self::METRIC_CODE, '12', ['state' => 'complete', 'store_code' => 'default']],
            [self::METRIC_CODE, '3', ['state' => 'processing', 'store_code' => 'eu']],
        ];
        $callCount = 0;
        $this->updateMetricService->expects($this->exactly(count($rows)))
            ->method('update')
            ->willReturnCallback(function (...$args) use (&$callCount, $expected) {
                self::assertEquals($expected[$callCount], $args);
                $callCount++;

                return true;
            });

        self::assertTrue($this->subject->aggregate());
    }

    public function test_it_returns_true_without_updating_when_there_are_no_orders(): void
    {
        $this->mockResetMetrics();

        $connection = $this->createMock(AdapterInterface::class);
        $this->resourceConnection->method('getConnection')->willReturn($connection);
        $connection->method('getTableName')->willReturnArgument(0);
        $connection->expects($this->once())->method('fetchAll')->willReturn([]);

        $this->updateMetricService->expects($this->never())->method('update');

        self::assertTrue($this->subject->aggregate());
    }
}
