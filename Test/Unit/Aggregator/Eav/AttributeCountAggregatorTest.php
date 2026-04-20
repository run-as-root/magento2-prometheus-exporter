<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Customer;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Eav\AttributeCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class AttributeCountAggregatorTest extends TestCase
{
    /** @var AttributeCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    /** @var MockObject|AttributeRepositoryInterface */
    private $attributeRepository;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    /**  @var MockObject|SearchCriteriaBuilder */
    private $searchCriteriaBuilder;

    /** @var MockObject|SearchCriteriaInterface */
    private $searchCriteria;

    protected function setUp(): void
    {
        $this->updateMetricService   = $this->createMock(UpdateMetricService::class);
        $this->attributeRepository   = $this->createMock(AttributeRepositoryInterface::class);
        $this->resourceConnection    = $this->createMock(ResourceConnection::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteria        = $this->createMock(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilder->method('create')->willReturn($this->searchCriteria);

        $this->sut = new AttributeCountAggregator(
            $this->updateMetricService,
            $this->attributeRepository,
            $this->resourceConnection,
            $this->searchCriteriaBuilder
        );
    }

    public function testAggregate(): void
    {
        $select        = 'SELECT entity_type_code FROM eav_entity_type';
        $adapter       = $this->createMock(AdapterInterface::class);
        $searchResult1 = $this->createMock(SearchResultsInterface::class);
        $searchResult2 = $this->createMock(SearchResultsInterface::class);

        $this->resourceConnection
            ->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($adapter);

        $adapter->method('getTableName')->willReturnArgument(0);

        $adapter
            ->expects($this->once())
            ->method('fetchAll')
            ->with(...[$select])
            ->willReturn([['entity_type_code' => 'a'], ['entity_type_code' => 'b']]);

        $searchCriteria = $this->searchCriteria;
        $getListCallCount = 0;
        $this->attributeRepository
            ->expects($this->exactly(2))
            ->method('getList')
            ->willReturnCallback(function (...$args) use (&$getListCallCount, $searchCriteria, $searchResult1, $searchResult2) {
                $getListCallCount++;
                if ($getListCallCount === 1) {
                    $expected = ['a', $searchCriteria];
                    $this->assertEquals($expected, array_slice($args, 0, count($expected)));
                    return $searchResult1;
                }
                if ($getListCallCount === 2) {
                    $expected = ['b', $searchCriteria];
                    $this->assertEquals($expected, array_slice($args, 0, count($expected)));
                    return $searchResult2;
                }
                return null;
            });

        $searchResult1
            ->expects($this->once())
            ->method('getTotalCount')
            ->willReturn(4);

        $searchResult2
            ->expects($this->once())
            ->method('getTotalCount')
            ->willReturn(6);

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento_eav_attribute_count_total', '10']);

        $this->sut->aggregate();
    }
}
