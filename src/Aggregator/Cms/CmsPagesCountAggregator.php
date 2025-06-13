<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Cms;

use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CmsPagesCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_cms_page_count_total';

    private UpdateMetricService $updateMetricService;
    private PageRepositoryInterface $cmsRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;

    public function __construct(
        UpdateMetricService $updateMetricService,
        PageRepositoryInterface $cmsRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->cmsRepository = $cmsRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 CMS Page Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aggregate(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $cmsSearchResult = $this->cmsRepository->getList($searchCriteria);

        return $this->updateMetricService->update(self::METRIC_CODE, (string)$cmsSearchResult->getTotalCount());
    }
}
