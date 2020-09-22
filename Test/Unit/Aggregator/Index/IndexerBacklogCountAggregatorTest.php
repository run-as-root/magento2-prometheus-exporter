<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Customer;

use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Framework\Mview\View\StateInterface;
use Magento\Framework\Mview\ViewInterface;
use Magento\Indexer\Model\Indexer\Collection;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Aggregator\Index\IndexerBacklogCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class IndexerBacklogCountAggregatorTest extends TestCase
{
    /** @var IndexerBacklogCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    /** @var MockObject|CollectionFactory */
    private $indexerCollectionFactory;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|Collection */
    private $indexerCollection;

    protected function setUp()
    {
        $this->updateMetricService      = $this->createMock(UpdateMetricService::class);
        $this->indexerCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->indexerCollection        = $this->createMock(Collection::class);
        $this->logger                   = $this->createMock(LoggerInterface::class);

        $this->indexerCollectionFactory->method('create')->willReturn($this->indexerCollection);

        $this->sut = new IndexerBacklogCountAggregator(
            $this->updateMetricService,
            $this->indexerCollectionFactory,
            $this->logger
        );
    }

    public function testAggregate(): void
    {
        $this->setUpIndexCollection();

        $lables1 = [
            'isValid' => true,
            'title'   => 'name',
            'status'  => 'success',
        ];

        $lables2 = [
            'isValid' => true,
            'title'   => 'other_name',
            'status'  => 'failed',
        ];

        $this->updateMetricService
            ->expects($this->at(0))
            ->method('update')
            ->with(
                ...[
                       'magento_indexer_backlog_count_total',
                       '2',
                       $lables1
                   ]
            );

        $this->updateMetricService
            ->expects($this->at(1))
            ->method('update')
            ->with(
                ...[
                       'magento_indexer_backlog_count_total',
                       '3',
                       $lables2
                   ]
            );

        $this->sut->aggregate();
    }

    public function testExceptionBehaviour(): void
    {
        $this->setUpIndexCollection(true);

        $this->logger
            ->expects($this->at(0))
            ->method('critical')
            ->with(...[__('ERROR CTNEE')]);

        $this->logger
            ->expects($this->at(1))
            ->method('critical')
            ->with(...[__('ERROR RE')]);

        $this->updateMetricService
            ->expects($this->never())
            ->method('update');

        $this->sut->aggregate();
    }

    private function setUpIndexCollection(bool $testException = false): void
    {
        $indexer1   = $this->createMock(IndexerInterface::class);
        $indexer2   = $this->createMock(IndexerInterface::class);
        $view1      = $this->createMock(ViewInterface::class);
        $view2      = $this->createMock(ViewInterface::class);
        $changelog1 = $this->createMock(ChangelogInterface::class);
        $changelog2 = $this->createMock(ChangelogInterface::class);
        $state1     = $this->createMock(StateInterface::class);
        $state2     = $this->createMock(StateInterface::class);

        $indexer1->method('isValid')->willReturn(true);
        $indexer1->method('getTitle')->willReturn('name');
        $indexer1->method('getStatus')->willReturn('success');

        $indexer2->method('isValid')->willReturn(true);
        $indexer2->method('getTitle')->willReturn('other_name');
        $indexer2->method('getStatus')->willReturn('failed');

        $indexer1
            ->expects($this->once())
            ->method('getView')
            ->willReturn($view1);

        $indexer2
            ->expects($this->once())
            ->method('getView')
            ->willReturn($view2);

        $view1
            ->expects($this->once())
            ->method('getChangelog')
            ->willReturn($changelog1);

        $view1
            ->expects($this->once())
            ->method('getState')
            ->willReturn($state1);

        $view2
            ->expects($this->once())
            ->method('getChangelog')
            ->willReturn($changelog2);

        $view2
            ->expects($this->once())
            ->method('getState')
            ->willReturn($state2);

        $state1
            ->expects($this->once())
            ->method('getVersionId')
            ->willReturn(1);

        $state2
            ->expects($this->once())
            ->method('getVersionId')
            ->willReturn(2);

        $changelog1
            ->expects($this->once())
            ->method('getVersion')
            ->willReturn(33);

        $changelog2
            ->expects($this->once())
            ->method('getVersion')
            ->willReturn(22);

        $changelog1
            ->expects($this->once())
            ->method('getList')
            ->with(...[1, 33])
            ->willReturn(['1', '2']);

        $changelog2
            ->expects($this->once())
            ->method('getList')
            ->with(...[2, 22])
            ->willReturn(['1', '2', '3']);

        if ($testException) {
            $changelog1
                ->method('getList')
                ->willThrowException(new ChangelogTableNotExistsException(__('ERROR CTNEE')));

            $changelog2
                ->method('getList')
                ->willThrowException(new RuntimeException(__('ERROR RE')));
        }

        $this->indexerCollection
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$indexer1, $indexer2]);
    }
}
