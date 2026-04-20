<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Plugin\Cache;

use Magento\Framework\App\Cache\Manager;
use Magento\Framework\App\DeploymentConfig;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Plugin\Cache\ManagerPlugin;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class ManagerPluginTest extends TestCase
{
    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    /** @var MockObject|DeploymentConfig */
    private $deploymentConfig;

    private ManagerPlugin $sut;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricServiceInterface::class);
        $this->deploymentConfig = $this->createMock(DeploymentConfig::class);
        $this->sut = new ManagerPlugin($this->updateMetricService, $this->deploymentConfig);
    }

    public function testAfterFlushIncrementsCounterWhenMagentoInstalled(): void
    {
        $this->deploymentConfig->method('isAvailable')->willReturn(true);
        $subject = $this->createMock(Manager::class);

        $this->updateMetricService
            ->expects($this->once())
            ->method('increment')
            ->with('magento_cache_flush_count_total')
            ->willReturn(true);

        self::assertNull($this->sut->afterFlush($subject, null, ['config']));
    }

    public function testAfterFlushSkipsDuringSetupInstall(): void
    {
        $this->deploymentConfig->method('isAvailable')->willReturn(false);
        $subject = $this->createMock(Manager::class);

        $this->updateMetricService->expects($this->never())->method('increment');

        self::assertNull($this->sut->afterFlush($subject, null, ['config']));
    }

    public function testAfterFlushSwallowsIncrementExceptions(): void
    {
        $this->deploymentConfig->method('isAvailable')->willReturn(true);
        $subject = $this->createMock(Manager::class);

        $this->updateMetricService
            ->method('increment')
            ->willThrowException(new \RuntimeException('db temporarily unavailable'));

        // Must not re-throw.
        self::assertNull($this->sut->afterFlush($subject, null, ['config']));
    }
}
