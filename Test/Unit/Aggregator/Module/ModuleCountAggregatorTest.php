<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Module;

use Magento\Framework\Module\ModuleListInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Module\ModuleCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

final class ModuleCountAggregatorTest extends TestCase
{
    /** @var ModuleCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricServiceInterface */
    private $updateMetricService;

    /** @var MockObject|ModuleListInterface */
    private $moduleList;

    protected function setUp()
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->moduleList          = $this->createMock(ModuleListInterface::class);

        $this->sut = new ModuleCountAggregator(
            $this->updateMetricService,
            $this->moduleList
        );
    }

    public function testAggregate(): void
    {
        $this->moduleList
            ->expects($this->once())
            ->method('getAll')
            ->willReturn(
                [
                    ['name' => 'Magento_Abc'],
                    ['name' => 'Magento_Def'],
                    ['name' => 'Peter_Abc']
                ]
            );

        $lables = [
            'magento_modules' => 2
        ];

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(
                ...[
                       'magento_module_installed_count_total',
                       '3',
                       $lables
                   ]
            );

        $this->sut->aggregate();
    }
}
