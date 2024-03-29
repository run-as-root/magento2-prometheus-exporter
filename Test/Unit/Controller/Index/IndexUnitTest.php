<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Controller\Index;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Context;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Controller\Index\Index;
use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use RunAsRoot\PrometheusExporter\Result\PrometheusResult;
use RunAsRoot\PrometheusExporter\Result\PrometheusResultFactory;

final class IndexUnitTest extends TestCase
{
    /**
     * @var Index
     */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var MetricRepository | MockObject $metricRepositoryMock */
        $metricRepositoryMock = $this->createMock(MetricRepository::class);

        /** @var SearchCriteriaBuilder | MockObject $searchResultBuilder */
        $searchResultBuilder = $this->createMock(SearchCriteriaBuilder::class);

        /** @var MetricAggregatorPool | MockObject $metricAggregatorPoolMock */
        $metricAggregatorPoolMock = $this->createMock(MetricAggregatorPool::class);

        /** @var Config | MockObject $configMock */
        $configMock = $this->createMock(Config::class);

        $prometheusResult = new PrometheusResult(
            $metricAggregatorPoolMock,
            $metricRepositoryMock,
            $searchResultBuilder,
            $configMock
        );

        /** @var Context| MockObject $contextMock */
        $contextMock = $this->createMock(Context::class);

        /** @var PrometheusResultFactory | MockObject $prometheusResultFactory */
        $prometheusResultFactory = $this->createMock(PrometheusResultFactory::class);
        $prometheusResultFactory->method('create')->willReturn($prometheusResult);

        $this->sut = new Index($contextMock, $prometheusResultFactory);
    }

    public function testExecuteReturnPrometheusResult(): void
    {
        $actual = $this->sut->execute();
        $expected = PrometheusResult::class;

        $this->assertInstanceOf($expected, $actual);
    }
}
