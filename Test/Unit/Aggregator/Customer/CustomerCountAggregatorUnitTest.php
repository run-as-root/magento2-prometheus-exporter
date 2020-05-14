<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Order;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\Search\SearchResult;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Customer\CustomerCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class CustomerCountAggregatorUnitTest extends TestCase
{
    /**
     * @var CustomerCountAggregator
     */
    private $sut;

    /** @var UpdateMetricService|MockObject */
    private $updateMetricService;

    /** @var CustomerRepositoryInterface|MockObject */
    private $customerRepository;

    /** @var SearchCriteriaBuilder|MockObject */
    private $searchCriteriaBuilder;

    /** @var StoreRepositoryInterface|MockObject */
    private $storeRepository;

    protected function setUp()
    {
        parent::setUp();

        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->customerRepository = $this->createMock(CustomerRepositoryInterface::class);
        $this->storeRepository = $this->createMock(StoreRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);

        $this->sut = new CustomerCountAggregator(
            $this->updateMetricService,
            $this->customerRepository,
            $this->storeRepository,
            $this->searchCriteriaBuilder
        );
    }

    public function testItShouldUpdateExistingMetric(): void
    {
        $storeId = 1;
        $storeCode = 'default';
        $totalCount = 1;
        $count = 1;
        $labels = [ 'store_code' => $storeCode ];

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $customer = $this->createMock(CustomerInterface::class);
        $customer->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $searchResult = $this->createMock(SearchResult::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $searchResult->expects($this->once())->method('getItems')->willReturn([ $customer ]);

        $this->customerRepository->expects($this->once())->method('getList')->with($searchCriteria)
                                 ->willReturn($searchResult);

        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())->method('getCode')->willReturn($storeCode);

        $this->storeRepository->expects($this->once())->method('getById')->with($storeId)->willReturn($store);

        $this->updateMetricService->expects($this->once())->method('update')
                                  ->with($this->sut->getCode(), $count, $labels);

        $result = $this->sut->aggregate();

        $this->assertTrue($result);
    }

    public function testItShouldUpdateExistingMetricByStore(): void
    {
        $storeId = 1;
        $storeIdTwo = 2;
        $storeCode = 'de';
        $storeCodeTwo = 'en';
        $totalCount = 2;
        $countOne = 1;
        $countTwo = 1;
        $labelsOne = [ 'store_code' => $storeCode ];
        $labelsTwo = [ 'store_code' => $storeCodeTwo ];

        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $customer = $this->createMock(CustomerInterface::class);
        $customer->expects($this->once())->method('getStoreId')->willReturn($storeId);

        $customerTwo = $this->createMock(CustomerInterface::class);
        $customerTwo->expects($this->once())->method('getStoreId')->willReturn($storeIdTwo);

        $items = [ $customer, $customerTwo ];

        $searchResult = $this->createMock(SearchResult::class);
        $searchResult->expects($this->once())->method('getTotalCount')->willReturn($totalCount);
        $searchResult->expects($this->once())->method('getItems')->willReturn($items);

        $this->customerRepository->expects($this->once())->method('getList')->with($searchCriteria)
                                 ->willReturn($searchResult);

        $store = $this->createMock(StoreInterface::class);
        $store->expects($this->once())->method('getCode')->willReturn($storeCode);

        $storeTwo = $this->createMock(StoreInterface::class);
        $storeTwo->expects($this->once())->method('getCode')->willReturn($storeCodeTwo);

        $this->storeRepository->expects($this->exactly(2))->method('getById')
                              ->withConsecutive([ $storeId ], [ $storeIdTwo ])
                              ->willReturnOnConsecutiveCalls($store, $storeTwo);

        $this->updateMetricService->expects($this->exactly(2))->method('update')
                                  ->withConsecutive(
                                      [ $this->sut->getCode(), $countOne, $labelsOne ],
                                      [ $this->sut->getCode(), $countTwo, $labelsTwo ]
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

        $this->customerRepository->expects($this->once())->method('getList')->with($searchCriteria)
                                 ->willReturn($searchResult);

        $this->updateMetricService->expects($this->never())->method('update');

        $result = $this->sut->aggregate();

        $this->assertTrue($result);
    }
}
