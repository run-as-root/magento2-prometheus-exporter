<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Customer;

use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Eav\AttributeCountAggregator;
use RunAsRoot\PrometheusExporter\Aggregator\Eav\AttributeSetCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class AttributeSetCountAggregatorTest extends TestCase
{
    /** @var AttributeSetCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    /** @var MockObject|AttributeSetRepositoryInterface */
    private $attributeSetRepository;

    /**  @var MockObject|SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var MockObject|SearchCriteriaInterface */
    private $searchCriteria;

    protected function setUp()
    {
        $this->updateMetricService    = $this->createMock(UpdateMetricService::class);
        $this->attributeSetRepository = $this->createMock(AttributeSetRepositoryInterface::class);
        $this->searchCriteriaBuilder  = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteria         = $this->createMock(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilder->method('create')->willReturn($this->searchCriteria);

        $this->sut = new AttributeSetCountAggregator(
            $this->updateMetricService,
            $this->attributeSetRepository,
            $this->searchCriteriaBuilder
        );
    }

    public function testAggregate(): void
    {
        $searchResults = $this->createMock(SearchResultsInterface::class);

        $this->attributeSetRepository
            ->expects($this->once())
            ->method('getList')
            ->with(...[$this->searchCriteria])
            ->willReturn($searchResults);

        $searchResults
            ->expects($this->once())
            ->method('getTotalCount')
            ->willReturn(10);

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento_eav_attribute_set_count_total', '10']);

        $this->sut->aggregate();
    }
}
