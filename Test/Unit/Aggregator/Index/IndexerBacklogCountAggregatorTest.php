<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Index;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Mview\View\ChangelogInterface;
use Magento\Framework\Mview\ViewInterface;
use Magento\Indexer\Model\Indexer\Collection;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Magento\Indexer\Model\Mview\View\State;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Index\IndexerBacklogCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class IndexerBacklogCountAggregatorTest extends TestCase
{
    private const VIEW_ID_1 = 'some_name';
    private const VIEW_ID_2 = 'some_name_another_name';
    private const METRIC_CODE = 'magento_indexer_backlog_count_total';

    private IndexerBacklogCountAggregator $sut;
    private MockObject|UpdateMetricServiceInterface $updateMetricService;
    private MockObject|Collection $indexerCollection;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $indexerCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->indexerCollection = $this->createMock(Collection::class);

        $indexerCollectionFactory->method('create')->willReturn($this->indexerCollection);

        $this->sut = new IndexerBacklogCountAggregator($this->updateMetricService, $indexerCollectionFactory);
    }

    public function testItAggregatesIndexersChangelogCount(): void
    {
        $indexer1 = $this->createMock(IndexerInterface::class);
        $indexer2 = $this->createMock(IndexerInterface::class);
        $view1 = $this->createMock(ViewInterface::class);
        $view2 = $this->createMock(ViewInterface::class);
        $changelog1 = $this->createMock(ChangelogInterface::class);
        $changelog2 = $this->createMock(ChangelogInterface::class);
        $stateMock1 = $this->createMock(State::class);
        $stateMock2 = $this->createMock(State::class);

        $testCurrentVersionId1 = '1111';
        $testCurrentVersionId2 = '2222';
        $testStateVersion1 = '1100';
        $testStateVersion2 = '2200';
        $testPendingCount1 = 11;
        $testPendingCount2 = 22;

        $indexer1->method('getTitle')->willReturn(self::VIEW_ID_1);
        $indexer2->method('getTitle')->willReturn(self::VIEW_ID_2);

        $indexer1->expects($this->once())->method('getView')->willReturn($view1);
        $indexer2->expects($this->once())->method('getView')->willReturn($view2);
        $view1->expects($this->once())->method('getChangelog')->willReturn($changelog1);
        $view2->expects($this->once())->method('getChangelog')->willReturn($changelog2);
        $view1->expects($this->once())->method('getState')->willReturn($stateMock1);
        $view2->expects($this->once())->method('getState')->willReturn($stateMock2);

        $changelog1->method('getVersion')->willReturn($testCurrentVersionId1);
        $changelog2->method('getVersion')->willReturn($testCurrentVersionId2);
        $stateMock1->method('getVersionId')->willReturn($testStateVersion1);
        $stateMock2->method('getVersionId')->willReturn($testStateVersion2);

        $changelog1->method('getList')
            ->with($testStateVersion1, $testCurrentVersionId1)
            ->willReturn(range(1, $testPendingCount1));
        $changelog2->method('getList')
            ->with($testStateVersion2, $testCurrentVersionId2)
            ->willReturn(range(1, $testPendingCount2));

        $this->indexerCollection->expects($this->once())->method('getItems')->willReturn([ $indexer1, $indexer2 ]);

        $lables1 = [ 'title' => self::VIEW_ID_1 ];
        $lables2 = [ 'title' => self::VIEW_ID_2 ];

        $this->updateMetricService
            ->expects($this->at(0))
            ->method('update')
            ->with(self::METRIC_CODE, '11', $lables1);

        $this->updateMetricService
            ->expects($this->at(1))
            ->method('update')
            ->with(self::METRIC_CODE, '22', $lables2);

        $this->sut->aggregate();
    }
}
