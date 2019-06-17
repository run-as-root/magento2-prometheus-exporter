<?php

declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Aggregator\Cms;

use Magento\Framework\Api\SearchCriteriaBuilder;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use Magento\Cms\Api\BlockRepositoryInterface;

class CmsBlocksCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_cms_block_count_total';

    /**
     * @var UpdateMetricService
     */
    private $updateMetricService;

    /**
     * @var BlockRepositoryInterface
     */
    private $cmsRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(
        UpdateMetricService $updateMetricService,
        BlockRepositoryInterface $cmsRepository,
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
        return 'Magento 2 CMS Block Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    /**
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function aggregate(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $cmsSearchResult = $this->cmsRepository->getList($searchCriteria);
        $this->updateMetricService->update(self::METRIC_CODE, (string)$cmsSearchResult->getTotalCount());

        return true;
    }
}
