<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Cron;

use Magento\Framework\App\CacheInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Cron\AggregateMetricsCron;
use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;

final class AggregateMetricsCronUnitTest extends TestCase
{
    /** @var MockObject|CacheInterface */
    private $cache;

    protected function setUp(): void
    {
        $this->cache = $this->createMock(CacheInterface::class);
    }

    private function makeSut(array $items, array $enabledMetrics): AggregateMetricsCron
    {
        $metricAggregatorPool = new MetricAggregatorPool($items);

        /** @var Config | MockObject $configMock */
        $configMock = $this->createMock(Config::class);
        $configMock->method('getMetricsStatus')->willReturn($enabledMetrics);

        /** @var MetricRepositoryInterface | MockObject $metricRepositoryMock */
        $metricRepositoryMock = $this->createMock(MetricRepositoryInterface::class);

        /** @var LoggerInterface | MockObject $loggerMock */
        $loggerMock = $this->createMock(LoggerInterface::class);

        return new AggregateMetricsCron(
            $metricAggregatorPool,
            $configMock,
            $metricRepositoryMock,
            $loggerMock,
            $this->cache
        );
    }

    public function testItShouldUpdateExistingMetric(): void
    {
        $aggregator = $this->createMock(MetricAggregatorInterface::class);
        $aggregator->expects($this->once())->method('aggregate');
        $aggregator->expects($this->once())->method('getCode')->willReturn('magento2_orders_count_total');

        $aggregatorTwo = $this->createMock(MetricAggregatorInterface::class);
        $aggregatorTwo->expects($this->once())->method('getCode')->willReturn('magento2_orders_count_other');

        $this->cache->expects($this->never())->method('load');
        $this->cache->expects($this->never())->method('save');

        $sut = $this->makeSut([$aggregator, $aggregatorTwo], [
            'magento2_orders_count_total',
            'magento2_orders_items_amount_total',
            'magento2_orders_items_count_total',
            'magento_cms_page_count_total',
        ]);

        $sut->execute();
    }

    public function test_it_skips_an_interval_aware_aggregator_that_ran_too_recently(): void
    {
        $aggregator = $this->createMock(ThrottledMetricAggregatorInterface::class);
        $aggregator->method('getCode')->willReturn('magento_orders_amount_total');
        $aggregator->method('getMinIntervalInSeconds')->willReturn(300);
        $aggregator->expects($this->never())->method('aggregate');

        $unrelated = $this->createMock(MetricAggregatorInterface::class);
        $unrelated->method('getCode')->willReturn('magento_cms_page_count_total');
        $unrelated->expects($this->once())->method('aggregate')->willReturn(true);

        $this->cache->expects($this->once())
            ->method('load')
            ->with('run_as_root_prometheus_last_aggregated_magento_orders_amount_total')
            ->willReturn('1');
        $this->cache->expects($this->never())->method('save');

        $sut = $this->makeSut([$aggregator, $unrelated], [
            'magento_orders_amount_total',
            'magento_cms_page_count_total',
        ]);

        $sut->execute();
    }

    public function test_it_runs_and_marks_an_interval_aware_aggregator_that_is_due(): void
    {
        $aggregator = $this->createMock(ThrottledMetricAggregatorInterface::class);
        $aggregator->method('getCode')->willReturn('magento_orders_amount_total');
        $aggregator->method('getMinIntervalInSeconds')->willReturn(300);
        $aggregator->expects($this->once())->method('aggregate')->willReturn(true);

        $this->cache->expects($this->once())
            ->method('load')
            ->with('run_as_root_prometheus_last_aggregated_magento_orders_amount_total')
            ->willReturn(false);
        $this->cache->expects($this->once())
            ->method('save')
            ->with('1', 'run_as_root_prometheus_last_aggregated_magento_orders_amount_total', [], 300);

        $sut = $this->makeSut([$aggregator], ['magento_orders_amount_total']);

        $sut->execute();
    }

    public function test_it_does_not_mark_an_interval_aware_aggregator_that_soft_fails(): void
    {
        $aggregator = $this->createMock(ThrottledMetricAggregatorInterface::class);
        $aggregator->method('getCode')->willReturn('magento_orders_amount_total');
        $aggregator->method('getMinIntervalInSeconds')->willReturn(300);
        $aggregator->expects($this->once())->method('aggregate')->willReturn(false);

        $this->cache->method('load')->willReturn(false);
        $this->cache->expects($this->never())->method('save');

        $sut = $this->makeSut([$aggregator], ['magento_orders_amount_total']);

        $sut->execute();
    }

    public function test_a_cache_exception_on_one_aggregator_does_not_abort_the_rest_of_the_loop(): void
    {
        $throttled = $this->createMock(ThrottledMetricAggregatorInterface::class);
        $throttled->method('getCode')->willReturn('magento_orders_amount_total');
        $throttled->method('getMinIntervalInSeconds')->willReturn(300);
        $throttled->expects($this->never())->method('aggregate');

        $unrelated = $this->createMock(MetricAggregatorInterface::class);
        $unrelated->method('getCode')->willReturn('magento_cms_page_count_total');
        $unrelated->expects($this->once())->method('aggregate')->willReturn(true);

        $this->cache->method('load')
            ->with('run_as_root_prometheus_last_aggregated_magento_orders_amount_total')
            ->willThrowException(new \RuntimeException('cache backend unavailable'));

        $sut = $this->makeSut([$throttled, $unrelated], [
            'magento_orders_amount_total',
            'magento_cms_page_count_total',
        ]);

        $sut->execute();
    }

    public function test_execute_only_ignores_the_throttle_so_the_cli_can_always_force_a_run(): void
    {
        $aggregator = $this->createMock(ThrottledMetricAggregatorInterface::class);
        $aggregator->method('getCode')->willReturn('magento_orders_amount_total');
        $aggregator->method('getMinIntervalInSeconds')->willReturn(300);
        $aggregator->expects($this->once())->method('aggregate')->willReturn(true);

        $this->cache->expects($this->never())->method('load');
        $this->cache->expects($this->never())->method('save');

        $sut = $this->makeSut([$aggregator], ['magento_orders_amount_total']);

        $sut->executeOnly('magento_orders_amount_total');
    }
}
