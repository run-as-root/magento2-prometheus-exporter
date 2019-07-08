<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Model\SourceModel\Metrics;

class ConfigUnitTest extends TestCase
{
    /**
     * @var Config
     */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        /** @var ScopeConfigInterface|\PHPUnit_Framework_MockObject_MockObject $scopeConfigMock */
        $scopeConfigMock = $this->createScopeConfigMock();
        /** @var Metrics|\PHPUnit_Framework_MockObject_MockObject $metricsSource */
        $metricsSource = $this->createMetricsSourceMock();

        $this->sut = new Config($scopeConfigMock, $metricsSource);
    }

    private function createScopeConfigMock()
    {
        $mock = $this->createMock(ScopeConfigInterface::class);

        $mock->method('getValue')->willReturn(implode(',', [
            'magento2_orders_count_total',
            'magento2_orders_items_amount_total',
            'magento2_orders_items_count_total',
            'magento_cms_page_count_total'
        ]));

        return $mock;
    }

    private function createMetricsSourceMock()
    {
        $mock = $this->createMock(Metrics::class);

        $mock->method('toOptionArray')->willReturn([
            ['label' => 'magento2_orders_count_total', 'value' => 'magento2_orders_count_total'],
            ['label' => 'magento2_orders_items_amount_total', 'value' => 'magento2_orders_items_amount_total'],
            ['label' => 'magento2_orders_items_count_total', 'value' => 'magento2_orders_items_count_total'],
            ['label' => 'magento_cms_page_count_total', 'value' => 'magento_cms_page_count_total']
        ]);

        return $mock;
    }

    public function testConfigShouldBeTrue() : void
    {
        $actual   = $this->sut->getMetricsStatus();
        $expected = [
            'magento2_orders_count_total',
            'magento2_orders_items_amount_total',
            'magento2_orders_items_count_total',
            'magento_cms_page_count_total',
        ];

        $this->assertEquals($expected, $actual);
    }

    public function testGetDefaultMetricsShouldReturnSourceValues() : void
    {
        $actual = $this->sut->getDefaultMetrics();
        $expected = [
            'magento2_orders_count_total',
            'magento2_orders_items_amount_total',
            'magento2_orders_items_count_total',
            'magento_cms_page_count_total',
        ];

        $this->assertEquals($expected, $actual);
    }
}
