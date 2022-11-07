<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Cms;

use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\CronJob\BrokenCronJobCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class BrokenCronJobCountAggregatorTest extends TestCase
{
    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|CollectionFactory */
    private $collectionFactory;

    /** @var MockObject|Collection */
    private $collection;

    /** @var BrokenCronJobCountAggregator */
    private $sut;

    public function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->collectionFactory   = $this->createMock(CollectionFactory::class);
        $this->collection          = $this->createMock(Collection::class);

        $this->collectionFactory->method('create')->willReturn($this->collection);

        $this->sut = new BrokenCronJobCountAggregator(
            $this->updateMetricService,
            $this->collectionFactory
        );
    }

    public function testAggregate()
    {
        $this->collection
            ->expects($this->at(0))
            ->method('addFilter')
            ->with(...['status', 'pending'])
            ->willReturn($this->collection);
        $this->collection
            ->expects($this->at(1))
            ->method('addFilter')
            ->with(...['executed_at', 'NULL', 'IS NOT'])
            ->willReturn($this->collection);
        $this->collection
            ->expects($this->at(2))
            ->method('addFilter')
            ->with(...['finished_at', 'NULL', 'IS'])
            ->willReturn($this->collection);

        $this->collection
            ->expects($this->once())
            ->method('count')
            ->willReturn(5);

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento_cronjob_broken_count_total', '5']);

        $this->sut->aggregate();
    }
}
