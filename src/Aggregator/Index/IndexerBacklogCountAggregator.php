<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Index;

use Magento\Framework\Indexer\IndexerInterface;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class IndexerBacklogCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_indexer_backlog_count_total';

    public function __construct(
        private readonly UpdateMetricService $updateMetricService,
        private readonly CollectionFactory $indexerCollectionFactory,
        private readonly LoggerInterface $logger
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
            if (!$indexer->isScheduled()) {
                continue;
            }

            $labels = ['title' => $indexer->getTitle()];

            $view = $indexer->getView();
            $changelog = $view->getChangelog();
            $state = $view->getState();

            try {
                $currentVersionId = $changelog->getVersion();
                $stateVersion = $state->getVersionId();

                $pendingCount = \count($changelog->getList($stateVersion, $currentVersionId));
                $this->updateMetricService->update(self::METRIC_CODE, (string) $pendingCount, $labels);
            } catch (ChangelogTableNotExistsException $e) {
                $this->logger->error($e->getMessage());
                continue;
            }
        }

        return true;
    }
}
