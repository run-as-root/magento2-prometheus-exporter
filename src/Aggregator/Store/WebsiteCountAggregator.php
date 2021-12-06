<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Store;

use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Api\WebsiteRepositoryInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class WebsiteCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_website_count_total';

    private UpdateMetricService $updateMetricService;

    private WebsiteRepositoryInterface $websiteRepository;

    /**
     * @param UpdateMetricService $updateMetricService
     * @param WebsiteRepositoryInterface $websiteRepository
     */
    public function __construct(
        UpdateMetricService $updateMetricService,
        WebsiteRepositoryInterface $websiteRepository
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->websiteRepository = $websiteRepository;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Website Count.';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $websiteList = $this->websiteRepository->getList();
        $websiteCount = (string)count($websiteList);

        return $this->updateMetricService->update(self::METRIC_CODE, $websiteCount);
    }
}
