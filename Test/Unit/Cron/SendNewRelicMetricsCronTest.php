<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Cron;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchResultsInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\NewRelicApi\Response\Metric\MetricPostResponse;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Cron\SendNewRelicMetricsCron;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Exception\PostMetricException;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Logger\MetricLogger;
use RunAsRoot\PrometheusExporter\NewRelicApi\Metric\MetricNewRelicApiInterface;
use RunAsRoot\PrometheusExporter\Data\NewRelicConfig;

class SendNewRelicMetricsCronTest extends TestCase
{
    /**
     * @var SendNewRelicMetricsCron
     */
    private $sut;

    /** @var NewRelicConfig|MockObject */
    private $newRelicConfig;

    /** @var MetricRepositoryInterface|MockObject */
    private $metricRepository;

    /** @var SearchCriteriaBuilder|MockObject */
    private $searchCriteriaBuilder;

    /** @var MetricNewRelicApiInterface|MockObject */
    private $metricNewRelicApi;

    /** @var MetricLogger|MockObject */
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->newRelicConfig = $this->createMock(NewRelicConfig::class);
        $this->metricRepository = $this->createMock(MetricRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->metricNewRelicApi = $this->createMock(MetricNewRelicApiInterface::class);
        $this->logger = $this->createMock(MetricLogger::class);

        $this->sut = new SendNewRelicMetricsCron(
            $this->newRelicConfig,
            $this->metricRepository,
            $this->searchCriteriaBuilder,
            $this->metricNewRelicApi,
            $this->logger
        );
    }

    public function testItShouldSendMetric(): void
    {
        $isNewRelicEnabled = true;
        $isNewRelicCronEnabled = true;
        $allowedNewRelicMetrics = ['magento_cronjob_count_total', 'magento_active_payment_methods_count_total'];

        $metricResponse = new MetricPostResponse();
        $metricResponse->setRequestId('aaaaaaaa-0000-1111-0000-bbbbbbbbbbbb');

        $metric = $this->createMock(MetricInterface::class);
        $searchCriteria = $this->createMock(SearchCriteria::class);
        $searchResults = $this->createMock(SearchResultsInterface::class);

        $this->newRelicConfig->expects($this->once())->method('isEnabled')->willReturn($isNewRelicEnabled);
        $this->newRelicConfig->expects($this->once())->method('isCronEnabled')->willReturn($isNewRelicCronEnabled);
        $this->newRelicConfig->expects($this->once())->method('getMetricsStatus')->willReturn($allowedNewRelicMetrics);

        $this->searchCriteriaBuilder->expects($this->once())->method('addFilter')
            ->with('code', $allowedNewRelicMetrics, 'in');
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $this->metricRepository->expects($this->once())->method('getList')->with($searchCriteria)->willReturn($searchResults);

        $searchResults->expects($this->once())->method('getTotalCount')->willReturn(1);
        $searchResults->expects($this->once())->method('getItems')->willReturn([$metric]);

        $this->metricNewRelicApi->expects($this->once())->method('post')->with([$metric])->willReturn($metricResponse);

        $this->sut->execute();
    }
}
