<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Cron;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Cron\AggregateMetricsCron;
use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;

final class AggregateMetricsCronUnitTest extends TestCase
{
    public function testItShouldUpdateExistingMetric(): void
    {
        $aggregator = $this->createMock(MetricAggregatorInterface::class);
        $aggregator->expects($this->once())->method('aggregate');
        $aggregator->expects($this->once())->method('getCode')->willReturn('magento2_orders_count_total');

        $aggregatorTwo = $this->createMock(MetricAggregatorInterface::class);
        $aggregatorTwo->expects($this->once())->method('getCode')->willReturn('magento2_orders_count_other');

        /** @var Config | MockObject $configMock */
        $configMock = $this->createMock(Config::class);
        $configMock->expects($this->once())->method('getMetricsStatus')->willReturn([
            'magento2_orders_count_total',
            'magento2_orders_items_amount_total',
            'magento2_orders_items_count_total',
            'magento_cms_page_count_total',
        ]);

        $items = [ $aggregator, $aggregatorTwo ];

        $metricAggregatorPool = new MetricAggregatorPool($items);

        $sut = new AggregateMetricsCron($metricAggregatorPool, $configMock);

        $sut->execute();
    }
}
