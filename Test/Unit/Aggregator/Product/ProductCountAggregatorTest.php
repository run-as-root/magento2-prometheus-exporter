<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Module;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Product\ProductCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class ProductCountAggregatorTest extends TestCase
{
    /** @var ProductCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->resourceConnection  = $this->createMock(ResourceConnection::class);

        $this->sut = new ProductCountAggregator(
            $this->updateMetricService,
            $this->resourceConnection
        );
    }

    public function testAggregate(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        $this->resourceConnection
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapter);

        $select = 'SELECT COUNT(entity_id) as ProductCount FROM catalog_product_entity;';

        $adapter
            ->expects($this->once())
            ->method('fetchOne')
            ->with(...[$select])
            ->willReturn('123');

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento_products_count_total', '123']);

        $this->sut->aggregate();
    }
}
