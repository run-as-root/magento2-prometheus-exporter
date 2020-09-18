<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Cms;

use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Cron\Model\ResourceModel\Schedule\CollectionFactory;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\CronJob\BrokenCronJobCountAggregator;
use RunAsRoot\PrometheusExporter\Aggregator\CronJob\CronJobCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use Zend_Db_Select;
use Zend_Db_Statement_Interface;

class CronJobCountAggregatorTest extends TestCase
{
    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|CollectionFactory */
    private $collectionFactory;

    /** @var MockObject|Collection */
    private $collection;

    /** @var BrokenCronJobCountAggregator */
    private $sut;

    public function setUp()
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->collectionFactory   = $this->createMock(CollectionFactory::class);
        $this->collection          = $this->createMock(Collection::class);

        $this->collectionFactory->method('create')->willReturn($this->collection);

        $this->sut = new CronJobCountAggregator(
            $this->updateMetricService,
            $this->collectionFactory
        );
    }

    public function testAggregate()
    {
        $select           = $this->createMock(Select::class);
        $zendDbSelect     = $this->createMock(Zend_Db_Select::class);
        $adapterInterface = $this->createMock(AdapterInterface::class);
        $zendDbStatment   = $this->createMock(Zend_Db_Statement_Interface::class);

        $this->collection
            ->method('removeAllFieldsFromSelect')
            ->willReturn($this->collection);

        $this->collection
            ->method('addFieldToSelect')
            ->with(...[['status', 'job_code']])
            ->willReturn($this->collection);

        $this->collection
            ->method('addExpressionFieldToSelect')
            ->with(...['count', 'COUNT(job_code)', ['job_code', 'status']])
            ->willReturn($this->collection);

        $this->collection
            ->method('getSelect')
            ->willReturn($select);

        $select
            ->method('group')
            ->with(...[['job_code', 'status']])
            ->willReturn($zendDbSelect);

        $this->collection
            ->method('getConnection')
            ->willReturn($adapterInterface);

        $adapterInterface
            ->method('query')
            ->with(...[$zendDbSelect])
            ->willReturn($zendDbStatment);

        $zendDbStatment
            ->method('fetchAll')
            ->willReturn(
                [
                    ['status' => 'nope', 'job_code' => 'some_code', 'count' => 10],
                    ['status' => 'yes', 'job_code' => 'some_other_code', 'count' => 20]
                ]
            );

        $labels1 = ['status' => 'nope', 'job_code' => 'some_code'];
        $labels2 = ['status' => 'yes', 'job_code' => 'some_other_code'];

        $this->updateMetricService
            ->expects($this->at(0))
            ->method('update')
            ->with(...['magento_cronjob_count_total', '10', $labels1]);

        $this->updateMetricService
            ->expects($this->at(1))
            ->method('update')
            ->with(...['magento_cronjob_count_total', '20', $labels2]);

        $this->sut->aggregate();
    }
}
