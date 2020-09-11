<?php

declare(strict_types = 1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Module;

use Magento\Framework\Module\ModuleListInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;
use function str_starts_with;

class ModuleCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_module_installed_count_total';

    private $updateMetricService;

    private $moduleList;

    public function __construct(UpdateMetricServiceInterface $updateMetricService, ModuleListInterface $moduleList)
    {
        $this->updateMetricService = $updateMetricService;
        $this->moduleList          = $moduleList;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Count of all installed Magento 2 Modules.';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $magentoModulesCount = 0;
        $modules             = $this->moduleList->getAll();

        foreach ($modules as $module) {
            if (str_starts_with($module['name'], 'Magento_')) {
                $magentoModulesCount++;
            }
        }

        $this->updateMetricService->update($this->getCode(), (string)count($modules), [
            'magento_modules' => $magentoModulesCount,
        ]);

        return true;
    }
}
