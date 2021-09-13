<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Exception\PostMetricException;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\NewRelicApi\Metric\MetricNewRelicApiInterface;
use RunAsRoot\PrometheusExporter\Data\NewRelicConfig;

class SendNewRelicMetricsCron
{
    private NewRelicConfig $newRelicConfig;
    private MetricRepositoryInterface $metricRepository;
    private SearchCriteriaBuilder $searchCriteriaBuilder;
    private MetricNewRelicApiInterface $metricNewRelicApi;
    private LoggerInterface $logger;

    public function __construct(
        NewRelicConfig $newRelicConfig,
        MetricRepositoryInterface $metricRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        MetricNewRelicApiInterface $metricNewRelicApi,
        LoggerInterface $logger
    ) {
        $this->newRelicConfig = $newRelicConfig;
        $this->metricRepository = $metricRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->metricNewRelicApi = $metricNewRelicApi;
        $this->logger = $logger;
    }

    public function execute(): void
    {
        if (!$this->newRelicConfig->isEnabled() || !$this->newRelicConfig->isCronEnabled()) {
            return;
        }

        $allowedMetrics = $this->newRelicConfig->getMetricsStatus();
        if (count($allowedMetrics)  === 0) {
            return;
        }

        $this->searchCriteriaBuilder->addFilter('code', $allowedMetrics, 'in');
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $metricSearchResults = $this->metricRepository->getList($searchCriteria);
        if ($metricSearchResults->getTotalCount()  === 0) {
            return;
        }

        try {
            $response = $this->metricNewRelicApi->post($metricSearchResults->getItems());
        } catch (PostMetricException $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
