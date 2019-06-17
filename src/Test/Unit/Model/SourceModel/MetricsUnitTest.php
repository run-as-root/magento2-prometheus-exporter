<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Model\SourceModel;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;
use RunAsRoot\PrometheusExporter\Model\SourceModel\Metrics;

class MetricsUnitTest extends TestCase
{
    /**
     * @var Metrics
     */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        return;
        /** @var MetricAggregatorPool |MockObject $metricAggregatorPoolMock */
        $metricAggregatorPoolMock = $this->createMock(MetricAggregatorPool::class);

        $this->sut = new Metrics($metricAggregatorPoolMock);
    }

    public function testOptionsArray(): void
    {
        $this->markTestIncomplete();
        $actual = $this->sut->toOptionArray();
        $expected = [];
    }
}
