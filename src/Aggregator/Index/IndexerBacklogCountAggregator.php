<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Index;

use Magento\Framework\Exception\RuntimeException;
use Magento\Framework\Mview\View\ChangelogTableNotExistsException;
use Magento\Indexer\Model\Indexer\CollectionFactory;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class IndexerBacklogCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_indexer_backlog_count_total';

    private $updateMetricService;
    private $indexerCollectionFactory;
    private $logger;

    public function __construct(
        UpdateMetricService $updateMetricService,
        CollectionFactory $indexerCollectionFactory,
        LoggerInterface $logger
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->indexerCollectionFactory = $indexerCollectionFactory;
        $this->logger = $logger;
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
        $result = true;

        foreach ($this->indexerCollectionFactory->create()->getItems() as $index) {
            $labels = [
                'isValid' => $index->isValid(),
                'title' => $index->getTitle(),
                'status' => $index->getStatus(),
            ];

            $view = $index->getView();
            /** @var \Magento\Framework\Mview\View\Changelog $changelog */
            $changelog = $view->getChangelog();

            try {
                $value  = count($changelog->getList($view->getState()->getVersionId(), $changelog->getVersion()));
                $this->updateMetricService->update(self::METRIC_CODE, (string)$value, $labels);
            } catch (ChangelogTableNotExistsException $e) {
                $this->logger->critical($e->getMessage());
                $result = false;
            } catch (RuntimeException $e) {
                $this->logger->critical($e->getMessage());
                $result = false;
            }
        }

        return $result;
    }
}
