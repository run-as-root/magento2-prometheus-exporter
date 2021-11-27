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
use RunAsRoot\PrometheusExporter\Aggregator\Index\IndexerChangelogCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class IndexerChangelogCountAggregatorTest extends TestCase
{
    private const TABLE_CHANGELOG_1 = 'table_name_cl';
    private const TABLE_CHANGELOG_2 = 'table_name_2_cl';
    private const METRIC_CODE = 'magento_indexer_changelog_count_total';
    private const EXCEPTION_1 = 'Very Bad Exception';
    private const EXCEPTION_2 = 'Even Worse Exception';

    private IndexerChangelogCountAggregator $sut;

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

        $this->sut = new IndexerChangelogCountAggregator(
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

        $labels1 = [
            'isValid' => true,
            'title' => 'indexer_name_1',
            'status' => 'success',
        ];

        $labels2 = [
            'isValid' => true,
            'title' => 'indexer_name_2',
            'status' => 'failed',
        ];

        $this->updateMetricService
            ->expects($this->at(0))
            ->method('update')
            ->with(self::METRIC_CODE, '777', $labels1);

        $this->updateMetricService
            ->expects($this->at(1))
            ->method('update')
            ->with(self::METRIC_CODE, '888', $labels2);

        $this->sut->aggregate();
    }

    public function testExceptionBehaviour(): void
    {
        $this->setUpIndexCollection();
        $this->setUpSelects(true);

        $this->logger
            ->expects($this->at(0))
            ->method('critical')
            ->with(self::EXCEPTION_1);

        $this->logger
            ->expects($this->at(1))
            ->method('critical')
            ->with(self::EXCEPTION_2);

        $this->updateMetricService
            ->expects($this->never())
            ->method('update');

        $this->sut->aggregate();
    }

    private function setUpSelects(bool $throwException = false): void
    {
        $select1 = $this->createMock(Select::class);
        $select2 = $this->createMock(Select::class);

        $connection = $this->createMock(AdapterInterface::class);
        $this->resourceConnection->expects($this->once())->method('getConnection')
                                 ->willReturn($connection);
        $connection->expects($this->exactly(2))
                   ->method('select')
                   ->will($this->onConsecutiveCalls($select1, $select2));
        $connection->expects($this->exactly(2))
                   ->method('getTableName')
                   ->willReturnMap(
                       [
                           [self::TABLE_CHANGELOG_1, self::TABLE_CHANGELOG_1],
                           [self::TABLE_CHANGELOG_2, self::TABLE_CHANGELOG_2]
                       ]
                   );
        $select1 = $this->setUpSelectChangelog($select1, self::TABLE_CHANGELOG_1);
        $select2 = $this->setUpSelectChangelog($select2, self::TABLE_CHANGELOG_2);

        if (!$throwException) {
            $connection->expects($this->exactly(2))
                       ->method('fetchOne')
                       ->willReturnMap(
                           [
                               [$select1, [], 777],
                               [$select2, [], 888],
                           ]
                       );
        } else {
            $connection->expects($this->exactly(2))
                       ->method('fetchOne')
                       ->will(
                           $this->returnCallback(function ($arg) use ($select1, $select2)
                           {
                               if (spl_object_id($select1) === spl_object_id($arg)) {
                                   throw new \Zend_Db_Exception(self::EXCEPTION_1);
                               } elseif (spl_object_id($select2) === spl_object_id($arg)) {
                                   throw new \Zend_Db_Exception(self::EXCEPTION_2);
                               } else {
                                   return 20;
                               }
                           })
                       );
        }
    }

    private function setUpSelectChangelog(Select $select, string $changelogTableName): Select
    {
        $select->expects($this->once())->method('from')->with($changelogTableName)
               ->willReturn($select);
        $select->expects($this->once())->method('reset')->with(Select::COLUMNS)
               ->willReturn($select);
        $select->expects($this->once())->method('columns')->with(['size' => 'count(version_id)'])
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
        $indexer1->method('getTitle')->willReturn('indexer_name_1');
        $indexer1->method('getStatus')->willReturn('success');

        $indexer2->method('isValid')->willReturn(true);
        $indexer2->method('getTitle')->willReturn('indexer_name_2');
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
