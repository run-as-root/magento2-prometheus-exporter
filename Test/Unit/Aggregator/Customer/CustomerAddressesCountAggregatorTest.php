<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Customer\CustomerAddressesCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class CustomerAddressesCountAggregatorTest extends TestCase
{
    /** @var CustomerAddressesCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|CustomerRepositoryInterface */
    private $customerRepository;

    /**  @var MockObject|SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var MockObject|SearchCriteriaInterface */
    private $searchCriteria;

    protected function setUp()
    {
        $this->updateMetricService   = $this->createMock(UpdateMetricService::class);
        $this->customerRepository    = $this->createMock(CustomerRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteria        = $this->createMock(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilder->method('create')->willReturn($this->searchCriteria);

        $this->sut = new CustomerAddressesCountAggregator(
            $this->updateMetricService,
            $this->customerRepository,
            $this->searchCriteriaBuilder
        );
    }

    public function testAggregate(): void
    {
        $searchResults = $this->createMock(SearchResultsInterface::class);
        $customer1     = $this->createMock(CustomerInterface::class);
        $customer2     = $this->createMock(CustomerInterface::class);

        $this->customerRepository
            ->expects($this->once())
            ->method('getList')
            ->with(...[$this->searchCriteria])
            ->willReturn($searchResults);

        $searchResults
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$customer1, $customer2]);

        $customer1
            ->expects($this->once())
            ->method('getAddresses')
            ->willReturn(['address1', 'address2']);

        $customer2
            ->expects($this->once())
            ->method('getAddresses')
            ->willReturn(['address1', 'address2']);

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento2_customer_addresses_count_total', '4']);

        $this->sut->aggregate();
    }

    public function testCountShouldBeZeroIfRepositoryThrowsException(): void
    {
        $this->customerRepository
            ->expects($this->once())
            ->method('getList')
            ->willThrowException(new LocalizedException(__('ERROR')));

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento2_customer_addresses_count_total', '0']);

        $this->sut->aggregate();
    }
}
