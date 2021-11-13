<?php declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Shipment;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Shipment\ShipmentCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class ShipmentCountAggregatorTest extends TestCase
{
    private const METRIC_CODE = 'magento_shipments_count_total';
    private const T_SHIP = 'm2_sales_shipment';
    private const T_INV_SHIP = 'm2_inventory_shipment_source';
    private const T_STORE = 'm2_store';

    private ShipmentCountAggregator $subject;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);

        $this->subject = new ShipmentCountAggregator(
            $this->updateMetricService,
            $this->resourceConnection
        );
    }

    private function getStatisticData(): array
    {
        return [
            [
                'SHIPMENT_COUNT' => 111,
                'STORE_CODE' => 'default',
                'SOURCE_CODE' => 'default'
            ],
            [
                'SHIPMENT_COUNT' => 222,
                'STORE_CODE' => 'default',
                'SOURCE_CODE' => 'eu'
            ],
            [
                'SHIPMENT_COUNT' => 333,
                'STORE_CODE' => 'nl',
                'SOURCE_CODE' => 'eu'
            ]
        ];
    }

    private function getSelectMock(): MockObject
    {
        $select = $this->createMock(Select::class);
        $select->expects($this->once())
               ->method('from')
               ->with(['ss' => self::T_SHIP])
               ->willReturn($select);

        $select->expects($this->exactly(2))
               ->method('joinInner')
               ->withConsecutive(
                   [
                       ['iss' => self::T_INV_SHIP],
                       'ss.entity_id = iss.shipment_id',
                       ['source_code']
                   ],
                   [
                       ['s' => self::T_STORE],
                       'ss.store_id = s.store_id',
                       ['code']
                   ]

               )->willReturn($select);
        $select->expects($this->once())->method('reset')->with(Select::COLUMNS)->willReturn($select);
        $select->expects($this->once())->method('group')->with(['s.code', 'iss.source_code']);
        $select->expects($this->once())
               ->method('columns')
               ->with(
                   [
                       'SHIPMENT_COUNT' => 'COUNT(ss.entity_id)',
                       'STORE_CODE' => 's.code',
                       'SOURCE_CODE' => 'iss.source_code'
                   ]
               )->willReturn($select);

        return $select;
    }

    public function testAggregate(): void
    {
        $connection = $this->createMock(AdapterInterface::class);
        $statisticData = $this->getStatisticData();
        $select = $this->getSelectMock();

        $this->resourceConnection->expects($this->once())
                                 ->method('getConnection')
                                 ->with('sales')
                                 ->willReturn($connection);
        $connection->expects($this->once())->method('select')->willReturn($select);
        $connection->expects($this->once())->method('fetchAll')->with($select)->willReturn($statisticData);
        $connection->expects($this->exactly(3))
                   ->method('getTableName')
                   ->willReturnMap(
                       [
                           ['sales_shipment', self::T_SHIP],
                           ['inventory_shipment_source', self::T_INV_SHIP],
                           ['store', self::T_STORE]
                       ]
                   );

        $params = [];
        foreach ($statisticData as $datum) {
            $params[] = [
                self::METRIC_CODE,
                $datum['SHIPMENT_COUNT'],
                ['source' => $datum['SOURCE_CODE'], 'store_code' => $datum['STORE_CODE']]
            ];
        }

        $this->updateMetricService->expects($this->exactly(3))
                                  ->method('update')
                                  ->withConsecutive(...$params);

        $this->subject->aggregate();
    }
}
