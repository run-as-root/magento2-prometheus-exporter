<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Review;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Module\Manager as ModuleManager;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class ProductsWithBadReviewsCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_products_with_bad_reviews_count_total';
    private const RATING_THRESHOLD = 60;
    private const REVIEW_MODULE_NAME = 'Magento_Review';
    private const PRODUCT_ENTITY_TYPE = 1;

    private UpdateMetricServiceInterface $updateMetricService;
    private ResourceConnection $resourceConnection;
    private ModuleManager $moduleManager;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
        ResourceConnection $resourceConnection,
        ModuleManager $moduleManager
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->resourceConnection = $resourceConnection;
        $this->moduleManager = $moduleManager;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Count of products whose rating_summary is below 60 (approximately 3 of 5 stars), grouped by store.';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        if (!$this->moduleManager->isEnabled(self::REVIEW_MODULE_NAME)) {
            return true;
        }

        $connection = $this->resourceConnection->getConnection();
        $rows = $connection->fetchAll($this->buildSelect($connection));

        foreach ($rows as $row) {
            $count = (int) ($row['PRODUCT_COUNT'] ?? 0);
            $storeCode = (string) ($row['STORE_CODE'] ?? '');

            $this->updateMetricService->update(
                self::METRIC_CODE,
                (string) $count,
                ['store_code' => $storeCode]
            );
        }

        return true;
    }

    private function buildSelect(AdapterInterface $connection): Select
    {
        return $connection->select()
            ->from(['res' => $connection->getTableName('review_entity_summary')])
            ->joinInner(
                ['s' => $connection->getTableName('store')],
                's.store_id = res.store_id',
                []
            )
            ->where('res.entity_type = ?', self::PRODUCT_ENTITY_TYPE)
            ->where('res.rating_summary < ?', self::RATING_THRESHOLD)
            ->reset(Select::COLUMNS)
            ->columns([
                'STORE_CODE' => 's.code',
                'PRODUCT_COUNT' => 'COUNT(DISTINCT res.entity_pk_value)',
            ])
            ->group('s.code');
    }
}
