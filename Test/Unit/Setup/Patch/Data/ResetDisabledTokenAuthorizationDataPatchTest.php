<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Setup\Patch\Data;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Setup\Patch\Data\ResetDisabledTokenAuthorizationDataPatch;

final class ResetDisabledTokenAuthorizationDataPatchTest extends TestCase
{
    /** @var AdapterInterface|MockObject */
    private $connection;

    /** @var LoggerInterface|MockObject */
    private $logger;

    private ResetDisabledTokenAuthorizationDataPatch $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->connection = $this->createMock(AdapterInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        /** @var ModuleDataSetupInterface|Stub $moduleDataSetup */
        $moduleDataSetup = $this->createStub(ModuleDataSetupInterface::class);
        $moduleDataSetup->method('getConnection')->willReturn($this->connection);
        $moduleDataSetup->method('getTable')->willReturnCallback(
            static function (string $table): string {
                return 'prefix_' . $table;
            }
        );

        $this->sut = new ResetDisabledTokenAuthorizationDataPatch($moduleDataSetup, $this->logger);
    }

    public function test_it_removes_the_stored_off_values_of_the_token_authorization_flag_in_every_scope(): void
    {
        // No scope condition on purpose: a "No" on website or store level opens /metrics for that store as well.
        $this->connection->expects($this->once())
            ->method('delete')
            ->with(
                'prefix_core_config_data',
                [
                    'path = ?' => 'metric_configuration/security/enable_token',
                    "value IS NULL OR value IN ('', '0')",
                ]
            )
            ->willReturn(2);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('removed 2 stored "Enable token authorization: No" value(s)'));

        $this->assertSame($this->sut, $this->sut->apply());
    }

    public function test_it_does_not_log_when_nothing_was_stored(): void
    {
        $this->connection->expects($this->once())->method('delete')->willReturn(0);
        $this->logger->expects($this->never())->method('warning');

        $this->assertSame($this->sut, $this->sut->apply());
    }

    public function test_it_has_no_dependencies_and_no_aliases(): void
    {
        $this->connection->expects($this->never())->method('delete');
        $this->logger->expects($this->never())->method('warning');

        $this->assertSame([], ResetDisabledTokenAuthorizationDataPatch::getDependencies());
        $this->assertSame([], $this->sut->getAliases());
    }
}
