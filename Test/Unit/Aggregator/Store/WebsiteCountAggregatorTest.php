<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Store;

use Magento\Store\Api\WebsiteRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Store\WebsiteCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class WebsiteCountAggregatorTest extends TestCase
{
    private const METRIC_CODE = 'magento_website_count_total';

    private WebsiteCountAggregator $sut;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|WebsiteRepositoryInterface */
    private $websiteRepository;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->websiteRepository = $this->createMock(WebsiteRepositoryInterface::class);

        $this->sut = new WebsiteCountAggregator(
            $this->updateMetricService,
            $this->websiteRepository
        );
    }

    public function testAggregate(): void
    {
        $this->websiteRepository
            ->expects($this->once())
            ->method('getList')
            ->willReturn(['a', 'b', 'c', 'd', '3']);

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(self::METRIC_CODE, '5');

        $this->sut->aggregate();
    }
}
