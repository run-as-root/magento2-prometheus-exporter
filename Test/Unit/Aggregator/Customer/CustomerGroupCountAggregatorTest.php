<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Customer;

use Magento\Customer\Api\Data\GroupSearchResultsInterface;
use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Customer\CustomerGroupCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class CustomerGroupCountAggregatorTest extends TestCase
{
    /** @var CustomerGroupCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|GroupRepositoryInterface */
    private $groupRepository;

    /**  @var MockObject|SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var MockObject|SearchCriteriaInterface */
    private $searchCriteria;

    protected function setUp()
    {
        $this->updateMetricService   = $this->createMock(UpdateMetricService::class);
        $this->groupRepository       = $this->createMock(GroupRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteria        = $this->createMock(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilder->method('create')->willReturn($this->searchCriteria);

        $this->sut = new CustomerGroupCountAggregator(
            $this->updateMetricService,
            $this->groupRepository,
            $this->searchCriteriaBuilder
        );
    }

    public function testAggregate(): void
    {
        $groupSearchResults = $this->createMock(GroupSearchResultsInterface::class);

        $this->groupRepository
            ->expects($this->once())
            ->method('getList')
            ->with(...[$this->searchCriteria])
            ->willReturn($groupSearchResults);

        $groupSearchResults
            ->expects($this->once())
            ->method('getTotalCount')
            ->willReturn(4);

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento2_customer_group_count_total', '4']);

        $this->assertEquals(true, $this->sut->aggregate());
    }

    public function testTotalCountShouldBeMinusOneWhenExceptionOccurs(): void
    {
        $this->groupRepository
            ->expects($this->once())
            ->method('getList')
            ->willThrowException(new LocalizedException(__('ERROR')));

        $this->updateMetricService
            ->expects($this->never())
            ->method('update');

        $this->assertEquals(false, $this->sut->aggregate());
    }

    public function testAggregateShouldNotUpdateMetricIfCountIsUnderZero(): void
    {
        $groupSearchResults = $this->createMock(GroupSearchResultsInterface::class);

        $this->groupRepository
            ->expects($this->once())
            ->method('getList')
            ->with(...[$this->searchCriteria])
            ->willReturn($groupSearchResults);

        $groupSearchResults
            ->expects($this->once())
            ->method('getTotalCount')
            ->willReturn(-1);

        $this->updateMetricService
            ->expects($this->never())
            ->method('update');

        $this->assertEquals(false, $this->sut->aggregate());
    }
}
