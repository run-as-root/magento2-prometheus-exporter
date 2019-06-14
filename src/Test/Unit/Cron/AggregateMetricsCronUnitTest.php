<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Order;

use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Cron\AggregateMetricsCron;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;

class AggregateMetricsCronUnitTest extends TestCase
{
    /**
     * @var AggregateMetricsCron
     */
    private $sut;

    public function testItShouldUpdateExistingMetric(): void
    {
        $aggregator = $this->createMock(MetricAggregatorInterface::class);
        $aggregator->expects($this->once())->method('aggregate');

        $aggregatorTwo = $this->createMock(MetricAggregatorInterface::class);
        $aggregatorTwo->expects($this->once())->method('aggregate');

        $items = [$aggregator, $aggregatorTwo];

        $metricAggregatorPool = new MetricAggregatorPool($items);

        $this->sut = new AggregateMetricsCron($metricAggregatorPool);

        $this->sut->execute();
    }
}
