<?php

declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Aggregator\Cms;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotSaveException;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use Magento\Cms\Api\PageRepositoryInterface;

class CmsPagesCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_cms_page_count_total';

    /**
     * @var UpdateMetricService
     */
    private $updateMetricService;

    /**
     * @var PageRepositoryInterface
     */
    private $cmsRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

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
