<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Module;

use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Store\StoreCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class StoreCountAggregatorTest extends TestCase
{
    /** @var StoreCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|StoreRepositoryInterface */
    private $storeRepository;

    protected function setUp()
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->storeRepository     = $this->createMock(StoreRepositoryInterface::class);

        $this->sut = new StoreCountAggregator(
            $this->updateMetricService,
            $this->storeRepository
        );
    }

    public function testAggregate(): void
    {
        $this->storeRepository
            ->expects($this->once())
            ->method('getList')
            ->willReturn(['a', 'b', 'c']);

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento_store_count_total', '3']);

        $this->sut->aggregate();
    }
}
