<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Product;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Product\ComplexProductVariationsAboveRecommendedLevelAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class ComplexProductVariationsAboveRecommendedLevelAggregatorTest extends TestCase
{
    private ComplexProductVariationsAboveRecommendedLevelAggregator $sut;

    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricServiceInterface::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);

        $this->sut = new ComplexProductVariationsAboveRecommendedLevelAggregator(
            $this->updateMetricService,
            $this->resourceConnection
        );
    }

    public function testMetadata(): void
    {
        self::assertSame('magento_complex_product_variations_above_recommended_level', $this->sut->getCode());
        self::assertSame('gauge', $this->sut->getType());
        self::assertStringContainsString('more than 50 variations', $this->sut->getHelp());
    }

    public function testAggregate(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        $this->resourceConnection
            ->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($adapter);

        $adapter->method('getTableName')->willReturnArgument(0);

        $adapter
            ->expects($this->once())
            ->method('fetchOne')
            ->with(
                $this->stringContains('catalog_product_super_link'),
                [50]
            )
            ->willReturn('7');

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with('magento_complex_product_variations_above_recommended_level', '7')
            ->willReturn(true);

        $result = $this->sut->aggregate();

        self::assertTrue($result);
    }
}
