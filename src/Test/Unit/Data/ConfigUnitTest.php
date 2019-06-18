<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Data;

use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Data\Config;
use Magento\Framework\App\Config\ScopeConfigInterface;

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
        $scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $scopeConfigMock->method('getValue')
            ->willReturn('magento2_orders_count_total,magento2_orders_items_amount_total,magento2_orders_items_count_total,magento_cms_page_count_total');

        $this->sut = new Config($scopeConfigMock);
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
}
