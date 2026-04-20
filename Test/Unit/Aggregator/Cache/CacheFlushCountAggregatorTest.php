<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Cache;

use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Cache\CacheFlushCountAggregator;

final class CacheFlushCountAggregatorTest extends TestCase
{
    private CacheFlushCountAggregator $sut;

    protected function setUp(): void
    {
        $this->sut = new CacheFlushCountAggregator();
    }

    public function testMetadata(): void
    {
        self::assertSame('magento_cache_flush_count_total', $this->sut->getCode());
        self::assertSame('counter', $this->sut->getType());
        self::assertStringContainsString('cache flush', $this->sut->getHelp());
    }

    public function testAggregateIsANoOp(): void
    {
        // Value is populated by the ManagerPlugin on each flush() call; the
        // aggregator exists only so the metric shows up in the pool.
        self::assertTrue($this->sut->aggregate());
    }
}
