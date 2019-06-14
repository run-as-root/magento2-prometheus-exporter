<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Service\UpdateMetricServiceUnitTest;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Model\MetricFactory;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class UpdateMetricServiceUnitTest extends TestCase
{
    /**
     * @var UpdateMetricService
     */
    private $sut;

    /** @var MetricRepositoryInterface|MockObject */
    private $metricRepository;

    /** @var MetricFactory|MockObject */
    private $metricFactory;

    protected function setUp()
    {
        parent::setUp();

        $this->metricRepository = $this->createMock(MetricRepositoryInterface::class);
        $this->metricFactory = $this->createMock(MetricFactory::class);

        $this->sut = new UpdateMetricService($this->metricRepository, $this->metricFactory);
    }

    public function testItShouldUpdateMetric(): void
    {
        $code = 'some-code';
        $value = 'some-value';
        $labels = [];

        $metric = $this->createMock(MetricInterface::class);
        $metric->expects($this->once())->method('setCode')->with($code);
        $metric->expects($this->once())->method('setValue')->with($value);
        $metric->expects($this->once())->method('setLabels')->with($labels);

        $this->metricRepository->expects($this->once())->method('getByCodeAndLabels')
                               ->with($code, $labels)->willReturn($metric);
        $this->metricRepository->expects($this->once())->method('save')->with($metric);

        $this->sut->update($code, $value, $labels);
    }

}
