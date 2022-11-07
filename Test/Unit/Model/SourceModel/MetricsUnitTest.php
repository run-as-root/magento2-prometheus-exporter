<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Model\SourceModel;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;
use RunAsRoot\PrometheusExporter\Model\SourceModel\Metrics;

final class MetricsUnitTest extends TestCase
{
    /**
     * @var Metrics
     */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $metricAggregatorMock = $this->createMock(MetricAggregatorInterface::class);
        $metricAggregatorMock->expects($this->exactly(2))->method('getCode')->willReturn('magento2_orders_count_total');

        /** @var MetricAggregatorPool |MockObject $metricAggregatorPoolMock */
        $metricAggregatorPoolMock = $this->createMock(MetricAggregatorPool::class);
        $metricAggregatorPoolMock->expects($this->once())->method('getItems')->willReturn([ $metricAggregatorMock ]);

        $this->sut = new Metrics($metricAggregatorPoolMock);
    }

    public function testOptionsArray(): void
    {
        $actual   = $this->sut->toOptionArray();
        $expected = [
            [
                'value' => 'magento2_orders_count_total',
                'label' => 'magento2_orders_count_total',
            ],
        ];

        $this->assertEquals($actual, $expected);
    }
}
