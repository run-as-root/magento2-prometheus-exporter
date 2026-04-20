<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Observer;

use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Observer\IncrementCacheFlushCounterObserver;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class IncrementCacheFlushCounterObserverTest extends TestCase
{
    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    private IncrementCacheFlushCounterObserver $sut;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricServiceInterface::class);
        $this->sut = new IncrementCacheFlushCounterObserver($this->updateMetricService);
    }

    public function testExecuteIncrementsCounter(): void
    {
        $observer = $this->createMock(Observer::class);

        $this->updateMetricService
            ->expects($this->once())
            ->method('increment')
            ->with('magento_cache_flush_count_total')
            ->willReturn(true);

        $this->sut->execute($observer);
    }

    public function testExecuteSwallowsExceptionsFromIncrement(): void
    {
        $observer = $this->createMock(Observer::class);

        $this->updateMetricService
            ->expects($this->once())
            ->method('increment')
            ->willThrowException(new \RuntimeException('table missing during setup:install'));

        // Must not re-throw. If it did, Magento's install process would crash.
        $this->sut->execute($observer);

        $this->expectNotToPerformAssertions();
    }
}
