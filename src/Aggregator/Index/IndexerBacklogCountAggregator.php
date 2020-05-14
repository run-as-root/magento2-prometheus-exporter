<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Index;

use Magento\Indexer\Model\Indexer\CollectionFactory;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class IndexerBacklogCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_indexer_backlog_count_total';

    private $updateMetricService;
    private $indexerCollectionFactory;

    public function __construct(UpdateMetricService $updateMetricService, CollectionFactory $indexerCollectionFactory)
    {
        $this->updateMetricService = $updateMetricService;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Indexer Backlog Item Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        foreach ($this->indexerCollectionFactory->create()->getItems() as $index) {
            $labels = [
                'isValid' => $index->isValid(),
                'title' => $index->getTitle(),
                'status' => $index->getStatus(),
            ];

            $view      = $index->getView();
            $changelog = $view->getChangelog();
            $value     = count($changelog->getList($view->getState()->getVersionId(), $changelog->getVersion()));

            $this->updateMetricService->update(self::METRIC_CODE, (string)$value, $labels);
        }

        return true;
    }
}
