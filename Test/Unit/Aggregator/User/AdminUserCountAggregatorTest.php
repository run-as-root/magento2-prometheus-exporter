<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Module;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\User\AdminUserCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class AdminUserCountAggregatorTest extends TestCase
{
    /** @var AdminUserCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->resourceConnection  = $this->createMock(ResourceConnection::class);

        $this->sut = new AdminUserCountAggregator(
            $this->updateMetricService,
            $this->resourceConnection
        );
    }

    public function testAggregate(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);

        $this->resourceConnection
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($adapter);

        $select = 'SELECT COUNT(user_id) FROM admin_user WHERE is_active = 1;';

        $adapter
            ->expects($this->once())
            ->method('fetchOne')
            ->with(...[$select])
            ->willReturn('44');

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento_admin_user_count', '44']);

        $this->sut->aggregate();
    }
}
