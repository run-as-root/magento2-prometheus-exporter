<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Order;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use function array_key_exists;

class OrderCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_orders_count_total';

    private MetricRepository $metricRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private UpdateMetricService $updateMetricService;
    private ResourceConnection $resourceConnection;

    public function __construct(
        MetricRepository $metricRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        UpdateMetricService $updateMetricService,
        ResourceConnection $resourceConnection
    ) {
        $this->metricRepository = $metricRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->updateMetricService = $updateMetricService;
        $this->resourceConnection = $resourceConnection;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento2 Order Count by state';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $this->resetMetrics();

        $connection = $this->resourceConnection->getConnection();

        $salesOrderTable = $connection->getTableName('sales_order'); 
        $storeTable = $connection->getTableName('store');        $query = 'SELECT COUNT(*) AS ORDER_COUNT, ORDER.state AS ORDER_STATE, STORE.code AS STORE_CODE' .
            ' FROM `sales_order` AS `ORDER`' .
            ' INNER JOIN `store` AS `STORE`' .
            ' ON ORDER.store_id = STORE.store_id' .
            ' GROUP BY ORDER.state, STORE.code';

        $orderSearchResult = $connection->fetchAll($query);

        if (count($orderSearchResult) === 0) {
            return true;
        }

        foreach ($orderSearchResult as $result) {
            $count = $result['ORDER_COUNT'] ?? 0;
            $orderState = $result['ORDER_STATE'] ?? '';
            $storeCode = $result['STORE_CODE'] ?? '';

            $labels = ['state' => $orderState, 'store_code' => $storeCode];

            $this->updateMetricService->update(self::METRIC_CODE, (string)$count, $labels);
        }

        return true;
    }

    protected function resetMetrics(): void
    {
        $searchCriteriaMetrics = $this->searchCriteriaBuilder->addFilter('code', self::METRIC_CODE)->create();
        $metricsSearchResult = $this->metricRepository->getList($searchCriteriaMetrics);
        $metrics = $metricsSearchResult->getItems();
        /** @var MetricInterface $metric */
        foreach ($metrics as $metric) {
            $metric->setValue("0");
            $this->metricRepository->save($metric);
        }
    }
}
