<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Setup\Patch\Data;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;

class CleanIndexerBacklogCountDataPatch implements DataPatchInterface
{
    private const METRIC_CODE = 'magento_indexer_backlog_count_total';

    public function __construct(
        private readonly MetricRepository $metricRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    public static function getDependencies(): array
    {
        return [];
    }

    public function getAliases(): array
    {
        return [];
    }

    /**
     * @throws CouldNotDeleteException
     */
    public function apply(): void
    {
        $searchCriteriaMetrics = $this->searchCriteriaBuilder->addFilter('code', self::METRIC_CODE)->create();
        $metricsSearchResult = $this->metricRepository->getList($searchCriteriaMetrics);
        $metrics = $metricsSearchResult->getItems();
        /** @var MetricInterface $metric */
        foreach ($metrics as $metric) {
            $this->metricRepository->delete($metric);
        }
    }
}
