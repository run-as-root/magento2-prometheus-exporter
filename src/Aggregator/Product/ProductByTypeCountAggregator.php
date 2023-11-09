<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Product;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class ProductByTypeCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_products_by_type_count_total';

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
        return 'Magento 2 Product by type Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $this->resetMetrics();

        $connection = $this->resourceConnection->getConnection('products');

        $productSearchResult = $connection->fetchAll($this->getSelect($connection));

        if (count($productSearchResult) === 0) {
            return true;
        }

        foreach ($productSearchResult as $result) {
            $count = $result['PRODUCT_COUNT'] ?? 0;
            $productType = $result['PRODUCT_TYPE'] ?? '';

            $labels = ['product_type' => $productType];

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

    private function getSelect(AdapterInterface $connection): Select
    {
        $select = $connection->select();

        $select->from(['p' => $connection->getTableName('catalog_product_entity')])
            ->reset(Select::COLUMNS)->columns(
                [
                    'PRODUCT_COUNT' => 'COUNT(*)',
                    'PRODUCT_TYPE' => 'p.type_id'
                ]
            )->group(['p.type_id']);

        return $select;
    }
}
