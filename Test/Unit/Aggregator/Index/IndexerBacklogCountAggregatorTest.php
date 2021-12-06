<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Index;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Mview\View\ChangelogInterface;
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
    private const TABLE_CHANGELOG_1 = 'table_name_cl';
    private const TABLE_CHANGELOG_2 = 'table_name_2_cl';
    private const VIEW_ID_1 = 'some_name';
    private const VIEW_ID_2 = 'some_name_another_name';
    private const TABLE_MVIEW_STATE = 'mview_state';
    private const METRIC_CODE = 'magento_indexer_backlog_count_total';

    private IndexerBacklogCountAggregator $sut;

    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    /** @var MockObject|CollectionFactory */
    private $indexerCollectionFactory;

    /** @var MockObject|LoggerInterface */
    private $logger;

    /** @var MockObject|Collection */
    private $indexerCollection;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->indexerCollectionFactory = $this->createMock(CollectionFactory::class);
        $this->indexerCollection = $this->createMock(Collection::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);

        $this->indexerCollectionFactory->method('create')->willReturn($this->indexerCollection);

        $this->sut = new IndexerBacklogCountAggregator(
            $this->updateMetricService,
            $this->indexerCollectionFactory,
            $this->resourceConnection,
            $this->logger
        );
    }

    public function testAggregate(): void
    {
        $this->setUpIndexCollection();
        $this->setUpSelects();

        $lables1 = [
            'isValid' => true,
            'title' => 'name',
            'status' => 'success',
        ];

        $lables2 = [
            'isValid' => true,
            'title' => 'other_name',
            'status' => 'failed',
        ];

        $this->updateMetricService
            ->expects($this->at(0))
            ->method('update')
            ->with(self::METRIC_CODE, '320', $lables1);

        $this->updateMetricService
            ->expects($this->at(1))
            ->method('update')
            ->with(self::METRIC_CODE, '33', $lables2);

        $this->sut->aggregate();
    }

    public function testExceptionBehaviour(): void
    {
        $this->setUpIndexCollection();
        $this->setUpSelects(true);

        $this->logger
            ->expects($this->at(0))
            ->method('critical')
            ->with(...['ERROR CTNEE']);

        $this->logger
            ->expects($this->at(1))
            ->method('critical')
            ->with(...['ERROR RE']);

        $this->updateMetricService
            ->expects($this->never())
            ->method('update');

        $this->sut->aggregate();
    }

    private function setUpSelects(bool $throwException = false): void
    {
        $select1 = $this->createMock(Select::class);
        $select2 = $this->createMock(Select::class);
        $select3 = $this->createMock(Select::class);
        $select4 = $this->createMock(Select::class);

        $connection = $this->createMock(AdapterInterface::class);
        $this->resourceConnection->expects($this->once())->method('getConnection')
                                 ->willReturn($connection);
        $connection->expects($this->exactly(4))
                   ->method('select')
                   ->will($this->onConsecutiveCalls($select1, $select2, $select3, $select4));
        $connection->expects($this->exactly(4))
                   ->method('getTableName')
                   ->willReturnMap(
                       [
                           [self::TABLE_CHANGELOG_1, self::TABLE_CHANGELOG_1],
                           [self::TABLE_CHANGELOG_2, self::TABLE_CHANGELOG_2],
                           [self::TABLE_MVIEW_STATE, self::TABLE_MVIEW_STATE]
                       ]
                   );
        $select1 = $this->setUpSelectChangelog($select1, self::TABLE_CHANGELOG_1);
        $select2 = $this->setUpSelectMviewState($select2, self::VIEW_ID_1);
        $select3 = $this->setUpSelectChangelog($select3, self::TABLE_CHANGELOG_2);
        $select4 = $this->setUpSelectMviewState($select4, self::VIEW_ID_2);

        if (!$throwException) {
            $connection->expects($this->exactly(4))
                       ->method('fetchOne')
                       ->willReturnMap(
                           [
                               [$select1, [], 550],
                               [$select2, [], 230],
                               [$select3, [], 99],
                               [$select4, [], 66]
                           ]
                       );
        } else {
            $connection->expects($this->exactly(4))
                       ->method('fetchOne')
                       ->will(
                           $this->returnCallback(function ($arg) use ($select2, $select4) {
                               if (spl_object_id($select2) === spl_object_id($arg)) {
                                   throw new \Zend_Db_Exception('ERROR CTNEE');
                               } elseif (spl_object_id($select4) === spl_object_id($arg)) {
                                   throw new \Zend_Db_Exception('ERROR RE');
                               } else {
                                   return 20;
                               }
                           })
                       );
        }
    }

    private function setUpSelectMviewState(Select $select, string $viewId): Select
    {
        $select->expects($this->once())->method('from')->with(self::TABLE_MVIEW_STATE)
               ->willReturn($select);
        $select->expects($this->once())->method('reset')->with(Select::COLUMNS)
               ->willReturn($select);
        $select->expects($this->once())->method('where')->with('view_id = ?', $viewId)
               ->willReturn($select);
        $select->expects($this->once())->method('columns')->with(['version_id'])
               ->willReturn($select);

        return $select;
    }

    private function setUpSelectChangelog(Select $select, string $changelogTableName): Select
    {
        $select->expects($this->once())->method('from')->with($changelogTableName)
               ->willReturn($select);
        $select->expects($this->once())->method('reset')->with(Select::COLUMNS)
               ->willReturn($select);
        $select->expects($this->once())->method('order')->with('version_id DESC')
               ->willReturn($select);
        $select->expects($this->once())->method('columns')->with(['version_id'])
               ->willReturn($select);

        return $select;
    }

    private function setUpIndexCollection(): void
    {
        $indexer1 = $this->createMock(IndexerInterface::class);
        $indexer2 = $this->createMock(IndexerInterface::class);
        $view1 = $this->createMock(ViewInterface::class);
        $view2 = $this->createMock(ViewInterface::class);
        $changelog1 = $this->createMock(ChangelogInterface::class);
        $changelog2 = $this->createMock(ChangelogInterface::class);

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

        $view2
            ->expects($this->once())
            ->method('getChangelog')
            ->willReturn($changelog2);

        $view1
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::VIEW_ID_1);

        $view2
            ->expects($this->once())
            ->method('getId')
            ->willReturn(self::VIEW_ID_2);

        $changelog1
            ->expects($this->once())
            ->method('getName')
            ->willReturn(self::TABLE_CHANGELOG_1);

        $changelog2
            ->expects($this->once())
            ->method('getName')
            ->willReturn(self::TABLE_CHANGELOG_2);

        $this->indexerCollection
            ->expects($this->once())
            ->method('getItems')
            ->willReturn([$indexer1, $indexer2]);
    }
}
