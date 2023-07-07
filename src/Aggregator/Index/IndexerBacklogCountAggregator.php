<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Index;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class IndexerBacklogCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_indexer_backlog_count_total';

    public function __construct(
        private readonly UpdateMetricService $updateMetricService,
        private readonly CollectionFactory $indexerCollectionFactory
    ) {
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
        /** @var IndexerInterface[] $indexers */
        $indexers = $this->indexerCollectionFactory->create()->getItems();
        foreach ($indexers as $indexer) {
            $labels = [ 'title' => $indexer->getTitle() ];

            $view = $indexer->getView();
            $changelog = $view->getChangelog();
            $state = $view->getState();

            $currentVersionId = $changelog->getVersion();
            $stateVersion = $state->getVersionId();

            $pendingCount = count($changelog->getList($stateVersion, $currentVersionId));
            $this->updateMetricService->update(self::METRIC_CODE, (string)$pendingCount, $labels);
        }

        return true;
    }
}
