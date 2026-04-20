<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Quote;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Quote\QuotesOverItemLimitCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class QuotesOverItemLimitCountAggregatorTest extends TestCase
{
    private QuotesOverItemLimitCountAggregator $sut;

    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    /** @var MockObject|ResourceConnection */
    private $resourceConnection;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricServiceInterface::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);

        $this->sut = new QuotesOverItemLimitCountAggregator(
            $this->updateMetricService,
            $this->resourceConnection
        );
    }

    public function testMetadata(): void
    {
        self::assertSame('magento_quotes_over_item_limit_count_total', $this->sut->getCode());
        self::assertSame('gauge', $this->sut->getType());
        self::assertStringContainsString('more than 100', $this->sut->getHelp());
    }

    public function testAggregateEmitsOneMetricPerStore(): void
    {
        $adapter = $this->createMock(AdapterInterface::class);
        $select = $this->createMock(Select::class);

        $this->resourceConnection
            ->expects($this->atLeastOnce())
            ->method('getConnection')
            ->willReturn($adapter);

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
                ['STORE_CODE' => 'default', 'QUOTE_COUNT' => '3'],
                ['STORE_CODE' => 'en', 'QUOTE_COUNT' => '1'],
            ]);

        $updateCallCount = 0;
        $this->updateMetricService
            ->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(function (...$args) use (&$updateCallCount) {
                $updateCallCount++;
                $expected = [
                    ['magento_quotes_over_item_limit_count_total', '3', ['store_code' => 'default']],
                    ['magento_quotes_over_item_limit_count_total', '1', ['store_code' => 'en']],
                ];
                self::assertEquals($expected[$updateCallCount - 1], array_slice($args, 0, 3));

                return true;
            });

        self::assertTrue($this->sut->aggregate());
    }
}
