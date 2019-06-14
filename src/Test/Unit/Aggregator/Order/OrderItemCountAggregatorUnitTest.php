<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Order;

use Magento\Framework\Api\Search\SearchResult;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Order\OrderItemCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class OrderItemCountAggregatorUnitTest extends TestCase
{
    /**
     * @var OrderItemCountAggregator
     */
    private $sut;

    /** @var UpdateMetricService|MockObject */
    private $updateMetricService;

    /** @var OrderRepositoryInterface|MockObject */
    private $orderRepository;

    /** @var SearchCriteriaBuilder|MockObject */
    private $searchCriteriaBuilder;

    protected function setUp()
    {
        parent::setUp();

        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->orderRepository = $this->createMock(OrderRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);

        $this->sut = new OrderItemCountAggregator(
            $this->updateMetricService,
            $this->orderRepository,
            $this->searchCriteriaBuilder
        );
    }

    public function testItShouldUpdateExistingMetric(): void
    {
        $totalCount = 1;
        $status = __('Shipped');
        $labels = ['status' => $status,];

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $orderItem = $this->createMock(OrderItem::class);
        $orderItem->expects($this->once())->method('getStatus')->willReturn($status);

        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('getItems')->willReturn([$orderItem]);

        $searchResult = $this->createMock(SearchResult::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$order]);

        $this->orderRepository->expects($this->once())->method('getList')->with($searchCriteria)
                              ->willReturn($searchResult);

        $this->updateMetricService->expects($this->once())->method('update')
                                  ->with($this->sut->getCode(), $totalCount, $labels);

        $result = $this->sut->aggregate();

        $this->assertTrue($result);
    }

    public function testItShouldUpdateExistingMetricByState(): void
    {
        $totalCount = 2;
        $totalCountOne = 1;
        $totalCountTwo = 1;
        $stateOne = __('Shipped');
        $stateTwo = __('Refunded');
        $labelsOne = ['status' => $stateOne,];
        $labelsTwo = ['status' => $stateTwo,];

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $orderItemOne = $this->createMock(OrderItem::class);
        $orderItemOne->expects($this->once())->method('getStatus')->willReturn($stateOne);

        $orderItemTwo = $this->createMock(OrderItem::class);
        $orderItemTwo->expects($this->once())->method('getStatus')->willReturn($stateTwo);

        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('getItems')->willReturn([$orderItemOne, $orderItemTwo]);

        $orders = [$order];

        $searchResult = $this->createMock(SearchResult::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $searchResult->expects($this->once())->method('getItems')->willReturn($orders);

        $this->orderRepository->expects($this->once())->method('getList')->with($searchCriteria)
                              ->willReturn($searchResult);

        $this->updateMetricService->expects($this->exactly(2))->method('update')
                                  ->withConsecutive(
                                      [$this->sut->getCode(), $totalCountOne, $labelsOne],
                                      [$this->sut->getCode(), $totalCountTwo, $labelsTwo]
                                  );

        $result = $this->sut->aggregate();

        $this->assertTrue($result);
    }

    public function testItShouldStopIfThereAreNoOrders(): void
    {
        $totalCount = 0;

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $searchResult = $this->createMock(SearchResult::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $searchResult->expects($this->never())->method('getItems');

        $this->orderRepository->expects($this->once())->method('getList')->with($searchCriteria)
                              ->willReturn($searchResult);

        $this->updateMetricService->expects($this->never())->method('update');

        $result = $this->sut->aggregate();

        $this->assertTrue($result);
    }

}
