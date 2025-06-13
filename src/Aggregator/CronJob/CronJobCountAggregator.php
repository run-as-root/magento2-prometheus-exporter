<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\CronJob;

use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;
use Zend_Db_Statement_Exception;

class CronJobCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_cronjob_count_total';

    private UpdateMetricService $updateMetricService;
    private CollectionFactory $cronCollectionFactory;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
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
        return 'Magento 2 CronJob Count.';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        /** @var Collection $collection */
        $collection = $this->cronCollectionFactory->create();
        $select = $collection->removeAllFieldsFromSelect()
                             ->addFieldToSelect([ 'status', 'job_code' ])
                             ->addExpressionFieldToSelect('count', 'COUNT(job_code)', [ 'job_code', 'status' ])
                             ->getSelect()
                             ->group([ 'job_code', 'status' ]);

        try {
            foreach ($collection->getConnection()->query($select)->fetchAll() as $item) {
                $labels = [
                    'status'   => $item['status'],
                    'job_code' => $item['job_code'],
                ];

                $this->updateMetricService->update($this->getCode(), (string)$item['count'], $labels);
            }
        } catch (Zend_Db_Statement_Exception $e) {
            // @todo Log this exception
            return false;
        }

        return true;
    }
}
