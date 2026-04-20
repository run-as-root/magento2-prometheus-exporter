<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Review;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Module\Manager as ModuleManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Review\ProductsWithBadReviewsCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class ProductsWithBadReviewsCountAggregatorTest extends TestCase
{
    private ProductsWithBadReviewsCountAggregator $sut;

    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    /** @var MockObject|ModuleManager */
    private $moduleManager;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricServiceInterface::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->moduleManager = $this->createMock(ModuleManager::class);

        $this->sut = new ProductsWithBadReviewsCountAggregator(
            $this->updateMetricService,
            $this->resourceConnection,
            $this->moduleManager
        );
    }

    public function testAggregateNoOpsWhenReviewModuleDisabled(): void
    {
        $this->moduleManager
            ->expects($this->once())
            ->method('isEnabled')
            ->with('Magento_Review')
            ->willReturn(false);

        $this->resourceConnection->expects($this->never())->method('getConnection');
        $this->updateMetricService->expects($this->never())->method('update');

        self::assertTrue($this->sut->aggregate());
    }

    public function testAggregateEmitsOneMetricPerStoreWhenReviewModuleEnabled(): void
    {
        $this->moduleManager
            ->method('isEnabled')
            ->with('Magento_Review')
            ->willReturn(true);

        $adapter = $this->createMock(AdapterInterface::class);
        $select = $this->createMock(Select::class);

        $this->resourceConnection->method('getConnection')->willReturn($adapter);
        $adapter->method('getTableName')->willReturnArgument(0);
        $adapter->method('select')->willReturn($select);

        $select->method('from')->willReturnSelf();
        $select->method('joinInner')->willReturnSelf();
        $select->method('where')->willReturnSelf();
        $select->method('reset')->willReturnSelf();
        $select->method('columns')->willReturnSelf();
        $select->method('group')->willReturnSelf();

        $adapter
            ->expects($this->once())
            ->method('fetchAll')
            ->with($select)
            ->willReturn([
                ['STORE_CODE' => 'default', 'PRODUCT_COUNT' => '2'],
            ]);

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(
                'magento_products_with_bad_reviews_count_total',
                '2',
                ['store_code' => 'default']
            )
            ->willReturn(true);

        self::assertTrue($this->sut->aggregate());
    }
}
