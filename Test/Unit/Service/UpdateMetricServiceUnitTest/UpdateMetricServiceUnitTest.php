<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Service\UpdateMetricServiceUnitTest;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Logger\MetricLogger;
use RunAsRoot\PrometheusExporter\Model\MetricFactory;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class UpdateMetricServiceUnitTest extends TestCase
{
    /**
     * @var UpdateMetricService
     */
    private $sut;

    /** @var MetricRepositoryInterface|MockObject */
    private $metricRepository;

    /** @var MetricFactory|MockObject */
    private $metricFactory;

    /** @var MetricLogger|MockObject */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->metricRepository = $this->createMock(MetricRepositoryInterface::class);
        $this->metricFactory = $this->createMock(MetricFactory::class);
        $this->logger = $this->createMock(MetricLogger::class);

        $this->sut = new UpdateMetricService($this->metricRepository, $this->metricFactory, $this->logger);
    }

    public function testItShouldUpdateExistingMetric(): void
    {
        $code = 'some-code';
        $value = 'some-value';
        $labels = [];

        $metric = $this->createMock(MetricInterface::class);
        $metric->expects($this->once())->method('setCode')->with($code);
        $metric->expects($this->once())->method('setValue')->with($value);
        $metric->expects($this->once())->method('setLabels')->with($labels);

        $this->metricFactory->expects($this->never())->method('create');

        $this->metricRepository->expects($this->once())->method('getByCodeAndLabels')
                               ->with($code, $labels)->willReturn($metric);
        $this->metricRepository->expects($this->once())->method('save')->with($metric);

        $result = $this->sut->update($code, $value, $labels);

        $this->assertTrue($result);
    }

    public function testItShouldUpdateCreateNewMetric(): void
    {
        $code = 'some-code';
        $value = 'some-value';
        $labels = [];

        $metric = $this->createMock(MetricInterface::class);
        $metric->expects($this->once())->method('setCode')->with($code);
        $metric->expects($this->once())->method('setValue')->with($value);
        $metric->expects($this->once())->method('setLabels')->with($labels);

        $this->metricFactory->expects($this->once())->method('create')->willReturn($metric);

        $this->metricRepository->expects($this->once())->method('getByCodeAndLabels')
                               ->with($code, $labels)->willThrowException(new NoSuchEntityException());
        $this->metricRepository->expects($this->once())->method('save')->with($metric);

        $result = $this->sut->update($code, $value, $labels);

        $this->assertTrue($result);
    }

    public function testItShouldCatchExceptionOnSaveAndReturnFalse(): void
    {
        $code = 'some-code';
        $value = 'some-value';
        $labels = [];

        $metric = $this->createMock(MetricInterface::class);
        $metric->expects($this->once())->method('setCode')->with($code);
        $metric->expects($this->once())->method('setValue')->with($value);
        $metric->expects($this->once())->method('setLabels')->with($labels);

        $this->metricFactory->expects($this->once())->method('create')->willReturn($metric);

        $e = new CouldNotSaveException(__('this is the message'));

        $this->metricRepository->expects($this->once())->method('getByCodeAndLabels')
                               ->with($code, $labels)->willThrowException(new NoSuchEntityException());
        $this->metricRepository->expects($this->once())->method('save')
                               ->with($metric)->willThrowException($e);

        $this->logger->expects($this->once())->method('error')->with($e->getMessage());

        $result = $this->sut->update($code, $value, $labels);

        $this->assertFalse($result);
    }
}
