<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\CronJob;

use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Cron\Model\Schedule;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class BrokenCronJobCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_cronjob_broken_count_total';

    private $updateMetricService;
    private $cronCollectionFactory;

    public function __construct(
        UpdateMetricService $updateMetricService,
        CollectionFactory $cronCollectionFactory
    ) {
        $this->updateMetricService   = $updateMetricService;
        $this->cronCollectionFactory = $cronCollectionFactory;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return <<<'TAG'
Magento 2 CronJob Broken Count. 
Broken CronJobs occur when when status is pending but execution_time is set.
TAG;
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        /** @var Collection $collection */
        $collection = $this->cronCollectionFactory->create();
        $collection->addFilter('status', Schedule::STATUS_PENDING)
            ->addFilter('executed_at', 'NULL', 'IS NOT')
            ->addFilter('finished_at', 'NULL', 'IS');
        $this->updateMetricService->update($this->getCode(), (string)$collection->count());

        return true;
    }
}
