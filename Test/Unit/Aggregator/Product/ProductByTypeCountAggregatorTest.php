<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Product;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Product\ProductByTypeCountAggregator;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class ProductByTypeCountAggregatorTest extends TestCase
{
    private const METRIC_CODE = 'magento_products_by_type_count_total';
    private const TABLE_PRODUCT = 'm2_catalog_product_entity';

    private ProductByTypeCountAggregator $subject;

    private MetricRepository $metricRepository;

    private SearchCriteriaBuilder $searchCriteriaBuilder;

    private SearchCriteriaInterface $searchCriteria;

    private MockObject|UpdateMetricService $updateMetricService;

    private MockObject|ResourceConnection $resourceConnection;

    protected function setUp(): void
    {
        $this->metricRepository = $this->createMock(MetricRepository::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->searchCriteria = $this->createMock(SearchCriteriaInterface::class);

        $this->searchCriteriaBuilder->method('create')->willReturn($this->searchCriteria);
        $this->searchCriteriaBuilder
            ->expects($this->once())
            ->method('addFilter')
            ->with('code', self::METRIC_CODE)
            ->willReturnSelf();

        $metric = $this->createMock(MetricInterface::class);
        $metric->expects($this->once())
            ->method('setValue')
            ->with('0');

        $searchResultsMock = $this->createMock(\Magento\Framework\Api\SearchResultsInterface::class);
        $searchResultsMock->expects($this->once())
            ->method('getItems')
            ->willReturn([$metric]);

        $this->metricRepository->expects($this->once())
            ->method('getList')
            ->with($this->searchCriteria)
            ->willReturn($searchResultsMock);

        $this->metricRepository->expects($this->once())
            ->method('save')
            ->with($metric);

        $this->subject = new ProductByTypeCountAggregator(
            $this->metricRepository,
            $this->searchCriteriaBuilder,
            $this->updateMetricService,
            $this->resourceConnection
        );
    }

    private function getStatisticData(): array
    {
        return [
            ['PRODUCT_COUNT' => 111, 'PRODUCT_TYPE' => 'bundle'],
            ['PRODUCT_COUNT' => 222, 'PRODUCT_TYPE' => 'configurable'],
            ['PRODUCT_COUNT' => 333, 'PRODUCT_TYPE' => 'giftcard'],
            ['PRODUCT_COUNT' => 444, 'PRODUCT_TYPE' => 'grouped'],
            ['PRODUCT_COUNT' => 555, 'PRODUCT_TYPE' => 'simple'],
            ['PRODUCT_COUNT' => 666, 'PRODUCT_TYPE' => 'virtual']
        ];
    }

    private function getSelectMock(): MockObject
    {
        $select = $this->createMock(Select::class);
        $select->expects($this->once())
            ->method('from')
            ->with(['p' => self::TABLE_PRODUCT])
            ->willReturn($select);

        $select->expects($this->once())->method('reset')->with(Select::COLUMNS)->willReturn($select);
        $select->expects($this->once())->method('group')->with(['p.type_id']);
        $select->expects($this->once())
            ->method('columns')
            ->with(['PRODUCT_COUNT' => 'COUNT(*)', 'PRODUCT_TYPE' => 'p.type_id'])
            ->willReturn($select);

        return $select;
    }

    public function testAggregate(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $statisticData = $this->getStatisticData();
        $select = $this->getSelectMock();

        $this->resourceConnection->expects($this->once())
            ->method('getConnection')
            ->with()
            ->willReturn($connection);

        $connection->expects($this->once())->method('select')->willReturn($select);
        $connection->method('getTableName')
            ->with('catalog_product_entity')
            ->willReturn(self::TABLE_PRODUCT);
        $connection->expects($this->once())->method('fetchAll')->with($select)->willReturn($statisticData);

        $params = [];
        foreach ($statisticData as $data) {
            $params[] = [
                self::METRIC_CODE,
                (string)$data['PRODUCT_COUNT'],
                ['product_type' => $data['PRODUCT_TYPE']]
            ];
        }

        $this->updateMetricService->expects($this->exactly(6))
            ->method('update')
            ->withConsecutive(...$params);

        $this->subject->aggregate();
    }
}
