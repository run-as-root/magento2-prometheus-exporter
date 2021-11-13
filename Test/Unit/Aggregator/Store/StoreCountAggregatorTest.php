<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Module;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Store\StoreCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class StoreCountAggregatorTest extends TestCase
{
    private const METRIC_CODE = 'magento_store_count_total';

    /** @var StoreCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|StoreRepositoryInterface */
    private $storeRepository;

    protected function setUp(): void
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
        $store1 = $this->createMock(StoreInterface::class);
        $store2 = $this->createMock(StoreInterface::class);
        $store3 = $this->createMock(StoreInterface::class);

        $store1->expects($this->once())->method('getIsActive')->willReturn(1);
        $store2->expects($this->once())->method('getIsActive')->willReturn(0);
        $store3->expects($this->once())->method('getIsActive')->willReturn(1);

        $this->storeRepository
            ->expects($this->once())
            ->method('getList')
            ->willReturn([$store1, $store2, $store3]);

        $this->updateMetricService
            ->expects($this->exactly(2))
            ->method('update')
            ->withConsecutive(
                [self::METRIC_CODE, '2', ['status' => 'enabled']],
                [self::METRIC_CODE, '1', ['status' => 'disabled']],
            );

        $this->sut->aggregate();
    }
}
