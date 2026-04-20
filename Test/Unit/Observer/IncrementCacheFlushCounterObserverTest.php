<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Observer;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Event\Observer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Observer\IncrementCacheFlushCounterObserver;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class IncrementCacheFlushCounterObserverTest extends TestCase
{
    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|DeploymentConfig */
    private $deploymentConfig;

    private IncrementCacheFlushCounterObserver $sut;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->sut = new IncrementCacheFlushCounterObserver(
            $this->updateMetricService,
            $this->deploymentConfig
        );
    }

    public function testExecuteIncrementsCounterWhenMagentoInstalled(): void
    {
        $this->deploymentConfig->method('isAvailable')->willReturn(true);
        $observer = $this->createMock(Observer::class);

        $this->updateMetricService
            ->expects($this->once())
            ->method('increment')
            ->with('magento_cache_flush_count_total')
            ->willReturn(true);

        $this->sut->execute($observer);
    }

    public function testExecuteSkipsDuringSetupInstall(): void
    {
        $this->deploymentConfig->method('isAvailable')->willReturn(false);
        $observer = $this->createMock(Observer::class);

        $this->updateMetricService->expects($this->never())->method('increment');

        $this->sut->execute($observer);
    }

    public function testExecuteSwallowsExceptionsFromIncrement(): void
    {
        $this->deploymentConfig->method('isAvailable')->willReturn(true);
        $observer = $this->createMock(Observer::class);

        $this->updateMetricService
            ->expects($this->once())
            ->method('increment')
            ->willThrowException(new \RuntimeException('table missing'));

        $this->sut->execute($observer);

        $this->expectNotToPerformAssertions();
    }
}
