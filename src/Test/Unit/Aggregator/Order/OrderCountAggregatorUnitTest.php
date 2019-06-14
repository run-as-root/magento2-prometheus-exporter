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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Order\OrderCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class OrderCountAggregatorUnitTest extends TestCase
{
    /**
     * @var OrderCountAggregator
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

        $this->sut = new OrderCountAggregator(
            $this->updateMetricService,
            $this->orderRepository,
            $this->searchCriteriaBuilder
        );
    }

    public function testItShouldUpdateExistingMetric(): void
    {
        $totalCount = 1;
        $count = 1;
        $state = 'processing';
        $labels = ['state' => $state,];

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('getState')->willReturn($state);

        $searchResult = $this->createMock(SearchResult::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $searchResult->expects($this->once())->method('getItems')->willReturn([$order]);

        $this->orderRepository->expects($this->once())->method('getList')->with($searchCriteria)
                              ->willReturn($searchResult);

        $this->updateMetricService->expects($this->once())->method('update')
                                  ->with($this->sut->getCode(), $count, $labels);

        $result = $this->sut->aggregate();

        $this->assertTrue($result);
    }

    public function testItShouldUpdateExistingMetricByState(): void
    {
        $totalCount = 2;
        $countProcessing = 1;
        $countPending = 1;
        $stateOne = 'processing';
        $stateTwo = 'pending';
        $labelsOne = ['state' => $stateOne,];
        $labelsTwo = ['state' => $stateTwo,];

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $order = $this->createMock(Order::class);
        $order->expects($this->once())->method('getState')->willReturn($stateOne);
        $orderTwo = $this->createMock(Order::class);
        $orderTwo->expects($this->once())->method('getState')->willReturn($stateTwo);

        $orders = [$order, $orderTwo];

        $searchResult = $this->createMock(SearchResult::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $searchResult->expects($this->once())->method('getItems')->willReturn($orders);

        $this->orderRepository->expects($this->once())->method('getList')->with($searchCriteria)
                              ->willReturn($searchResult);

        $this->updateMetricService->expects($this->exactly(2))->method('update')
                                  ->withConsecutive(
                                      [$this->sut->getCode(), $countProcessing, $labelsOne],
                                      [$this->sut->getCode(), $countPending, $labelsTwo]
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
