<?php declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Shipment;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class ShipmentCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_shipments_count_total';

    private UpdateMetricService $updateMetricService;

    private ResourceConnection $resourceConnection;

    /**
     * @param UpdateMetricService $updateMetricService
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        UpdateMetricService $updateMetricService,
        ResourceConnection  $resourceConnection
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->resourceConnection = $resourceConnection;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Shipments count by store and source.';
    }

    public function getType(): string
    {
        return 'counter';
    }

    public function aggregate(): bool
    {
        $connection = $this->resourceConnection->getConnection('sales');

        $statistic = $connection->fetchAll($this->getSelect($connection));

        foreach ($statistic as $result) {
            $count = $result['SHIPMENT_COUNT'] ?? 0;
            $store = $result['STORE_CODE'] ?? '';
            $source = $result['SOURCE_CODE'] ?? '';

            $labels = ['source' => $source, 'store_code' => $store];

            $this->updateMetricService->update(self::METRIC_CODE, (string)$count, $labels);
        }

        return true;
    }

    private function getSelect(AdapterInterface $connection): Select
    {
        $select = $connection->select();

        $select->from(['ss' => $connection->getTableName('sales_shipment')])
               ->joinInner(
                   ['iss' => $connection->getTableName('inventory_shipment_source')],
                   'ss.entity_id = iss.shipment_id',
                   ['source_code']
               )->joinInner(
                   ['s' => $connection->getTableName('store')],
                   'ss.store_id = s.store_id',
                   ['code']
               )->reset(Select::COLUMNS)->columns(
                   [
                    'SHIPMENT_COUNT' => 'COUNT(ss.entity_id)',
                    'STORE_CODE' => 's.code',
                    'SOURCE_CODE' => 'iss.source_code'
                ]
               )->group(['s.code', 'iss.source_code']);

        return $select;
    }
}
