<?php declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Shipment;

use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\DB\Sql\ExpressionFactory;
use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Category\CategoryCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class CategoryCountAggregatorTest extends TestCase
{
    private const METRIC_CODE = 'magento_catalog_category_count_total';
    private const TABLE_ATT = 'm2_eav_attribute';
    private const TABLE_CAT_ENT_INT = 'm2_catalog_category_entity_int';
    private const TABLE_CAT_ENT = 'm2_catalog_category_entity';
    private const TABLE_STORE_GROUP = 'm2_store_group';
    private const TABLE_STORE = 'm2_store';
    private const LINK_FIELD = 'row_id';
    private const ATTR_ID = 77;

    private CategoryCountAggregator $subject;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    /** @var MockObject|ExpressionFactory */
    private $expressionFactory;

    /** @var MockObject|MetadataPool */
    private $metadataPool;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->expressionFactory = $this->createMock(ExpressionFactory::class);
        $this->metadataPool = $this->createMock(MetadataPool::class);

        $this->subject = new CategoryCountAggregator(
            $this->updateMetricService,
            $this->resourceConnection,
            $this->metadataPool,
            $this->expressionFactory
        );
    }

    private function getStatisticData(): array
    {
        return [
            [
                'STORE_CODE' => 'default',
                'ACTIVE_IN_MENU' => 50,
                'ACTIVE_NOT_IN_MENU' => 10,
                'NOT_ACTIVE_IN_MENU' => 25,
                'NOT_ACTIVE_NOT_IN_MENU' => 15

            ],
            [
                'STORE_CODE' => 'base',
                'ACTIVE_IN_MENU' => 18,
                'ACTIVE_NOT_IN_MENU' => 3,
                'NOT_ACTIVE_IN_MENU' => 4,
                'NOT_ACTIVE_NOT_IN_MENU' => 1

            ],
            [
                'STORE_CODE' => 'eu',
                'ACTIVE_IN_MENU' => 79,
                'ACTIVE_NOT_IN_MENU' => 21,
                'NOT_ACTIVE_IN_MENU' => 15,
                'NOT_ACTIVE_NOT_IN_MENU' => 16

            ],
        ];
    }

    private function getSelectMock(): MockObject
    {
        $select = $this->createMock(Select::class);

        $fromCallCount = 0;
        $select->expects($this->exactly(3))->method('from')
               ->willReturnCallback(function (...$args) use (&$fromCallCount, $select) {
                   $expected = [
                       [self::TABLE_ATT],
                       [self::TABLE_ATT],
                       [["sg" => self::TABLE_STORE_GROUP]],
                   ];
                   $this->assertSame($expected[$fromCallCount] ?? null, $args);
                   $fromCallCount++;
                   return $select;
               });

        $whereCallCount = 0;
        $select->expects($this->exactly(4))->method('where')
               ->willReturnCallback(function (...$args) use (&$whereCallCount, $select) {
                   $expected = [
                       ['entity_type_id = ?', 3],
                       ['attribute_code = ?', 'is_active'],
                       ['entity_type_id = ?', 3],
                       ['attribute_code = ?', 'include_in_menu'],
                   ];
                   $this->assertSame($expected[$whereCallCount] ?? null, $args);
                   $whereCallCount++;
                   return $select;
               });
        $select->expects($this->exactly(3))
               ->method('reset')
               ->with(Select::COLUMNS)
               ->willReturn($select);

        $joinInnerCallCount = 0;
        $select->expects($this->exactly(3))->method('joinInner')
               ->willReturnCallback(function (...$args) use (&$joinInnerCallCount, $select) {
                   $expected = [
                       [
                           ['s' => self::TABLE_STORE],
                           'sg.group_id = s.group_id',
                       ],
                       [
                           ['cce1' => self::TABLE_CAT_ENT],
                           'sg.root_category_id = cce1.entity_id',
                       ],
                       [
                           ['cce2' => self::TABLE_CAT_ENT],
                           "cce2.path like CONCAT(cce1.path, '%')",
                       ],
                   ];
                   $this->assertSame($expected[$joinInnerCallCount] ?? null, $args);
                   $joinInnerCallCount++;
                   return $select;
               });

        $joinLeftCallCount = 0;
        $joinLeftExpected = [
            [
                ['ccei1' => self::TABLE_CAT_ENT_INT],
                sprintf(
                    "cce2.%s = ccei1.%s AND ccei1.attribute_id = %s AND ccei1.store_id = s.store_id",
                    self::LINK_FIELD,
                    self::LINK_FIELD,
                    self::ATTR_ID
                ),
            ],
            [
                ['ccei2' => self::TABLE_CAT_ENT_INT],
                sprintf(
                    "cce2.%s = ccei2.%s AND ccei2.attribute_id = %s AND ccei2.store_id = 0",
                    self::LINK_FIELD,
                    self::LINK_FIELD,
                    self::ATTR_ID
                ),
            ],
            [
                ['ccei3' => self::TABLE_CAT_ENT_INT],
                sprintf(
                    "cce2.%s = ccei3.%s AND ccei3.attribute_id = %s AND ccei3.store_id = s.store_id",
                    self::LINK_FIELD,
                    self::LINK_FIELD,
                    self::ATTR_ID
                ),
            ],
            [
                ['ccei4' => self::TABLE_CAT_ENT_INT],
                sprintf(
                    "cce2.%s = ccei4.%s AND ccei4.attribute_id = %s AND ccei4.store_id = 0",
                    self::LINK_FIELD,
                    self::LINK_FIELD,
                    self::ATTR_ID
                ),
            ],
        ];
        $select->expects($this->exactly(4))->method('joinLeft')
               ->willReturnCallback(function (...$args) use (&$joinLeftCallCount, $joinLeftExpected, $select) {
                   $this->assertSame($joinLeftExpected[$joinLeftCallCount] ?? null, $args);
                   $joinLeftCallCount++;
                   return $select;
               });

        $expressionMock = $this->createMock(Expression::class);
        $this->expressionFactory->expects($this->exactly(4))
                                ->method('create')
                                ->willReturnMap($this->getExpressionsMap($expressionMock));
        $columnsCallCount = 0;
        $columnsExpected = [
            [['attribute_id']],
            [['attribute_id']],
            [
                [
                    'STORE_CODE' => 's.code',
                    'ACTIVE_IN_MENU' => $expressionMock,
                    'ACTIVE_NOT_IN_MENU' => $expressionMock,
                    'NOT_ACTIVE_IN_MENU' => $expressionMock,
                    'NOT_ACTIVE_NOT_IN_MENU' => $expressionMock,
                ],
            ],
        ];
        $select->expects($this->exactly(3))->method('columns')
               ->willReturnCallback(function (...$args) use (&$columnsCallCount, $columnsExpected, $select) {
                   $this->assertSame($columnsExpected[$columnsCallCount] ?? null, $args);
                   $columnsCallCount++;
                   return $select;
               });

        $select->expects($this->once())->method('group')->with('s.code');

        return $select;
    }

    private function getExpressionsMap(Expression $expressionMock): array
    {
        $expression = 'COUNT((
            %s IF(ccei1.value IS NULL, ccei2.value, ccei1.value) AND
            %s IF(ccei3.value IS NULL, ccei4.value, ccei3.value)
        ) or null)';

        return [
            [['expression' => sprintf($expression, '', '')], $expressionMock],
            [['expression' => sprintf($expression, '', 'NOT')], $expressionMock],
            [['expression' => sprintf($expression, 'NOT', '')], $expressionMock],
            [['expression' => sprintf($expression, 'NOT', 'NOT')], $expressionMock],
        ];
    }

    private function getTableNamesMap(): array
    {
        return [
            ['eav_attribute', self::TABLE_ATT],
            ['catalog_category_entity_int', self::TABLE_CAT_ENT_INT],
            ['catalog_category_entity', self::TABLE_CAT_ENT],
            ['store', self::TABLE_STORE],
            ['store_group', self::TABLE_STORE_GROUP],
        ];
    }

    public function testAggregate(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $statisticData = $this->getStatisticData();
        $this->resourceConnection->expects($this->once())->method('getConnection')->willReturn($connection);
        $connection->expects($this->exactly(10))
                   ->method('getTableName')
                   ->willReturnMap($this->getTableNamesMap());
        $entityMetadata = $this->createMock(EntityMetadataInterface::class);
        $entityMetadata->expects($this->once())->method('getLinkField')->willReturn(self::LINK_FIELD);
        $this->metadataPool->expects($this->once())->method('getMetadata')
                           ->with(CategoryInterface::class)->willReturn($entityMetadata);

        $select = $this->getSelectMock();
        $connection->expects($this->exactly(3))->method('select')->willReturn($select);
        $connection->expects($this->exactly(2))->method('fetchOne')->willReturn(self::ATTR_ID);
        $connection->expects($this->once())
                   ->method('fetchAll')
                   ->with($select)
                   ->willReturn($statisticData);

        $updateExpected = $this->getUpdateMetricsArguments($statisticData);
        $updateCallCount = 0;
        $this->updateMetricService->expects($this->exactly(4 * count($statisticData)))
                                  ->method('update')
                                  ->willReturnCallback(function (...$args) use (&$updateCallCount, $updateExpected) {
                                      $this->assertSame($updateExpected[$updateCallCount] ?? null, $args);
                                      $updateCallCount++;
                                      return true;
                                  });

        $this->subject->aggregate();
    }

    private function getUpdateMetricsArguments(array $statisticData): array
    {
        $arguments = [];

        foreach ($statisticData as $datum) {
            $label = ['store_code' => $datum['STORE_CODE']];
            $arguments[] = [
                self::METRIC_CODE,
                $datum['ACTIVE_IN_MENU'],
                array_merge(['status' => 'enabled', 'menu_status' => 'enabled'], $label)
            ];
            $arguments[] = [
                self::METRIC_CODE,
                $datum['ACTIVE_NOT_IN_MENU'],
                array_merge(['status' => 'enabled', 'menu_status' => 'disabled'], $label)
            ];
            $arguments[] = [
                self::METRIC_CODE,
                $datum['NOT_ACTIVE_IN_MENU'],
                array_merge(['status' => 'disabled', 'menu_status' => 'enabled'], $label)
            ];
            $arguments[] = [
                self::METRIC_CODE,
                $datum['NOT_ACTIVE_NOT_IN_MENU'],
                array_merge(['status' => 'disabled', 'menu_status' => 'disabled'], $label)
            ];
        }

        return $arguments;
    }
}
