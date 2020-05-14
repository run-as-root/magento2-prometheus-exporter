<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Product;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class ProductCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_products_count_total';

    private $updateMetricService;
    private $productRepository;
    private $searchCriteriaBuilder;

    public function __construct(
        UpdateMetricService $updateMetricService,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Product Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $productSearchResult = $this->productRepository->getList($searchCriteria);

        return $this->updateMetricService->update(self::METRIC_CODE, (string)$productSearchResult->getTotalCount());
    }
}
