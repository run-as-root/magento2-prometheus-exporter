<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Data;

use PHPUnit\Framework\MockObject\MockObject;
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
        $scopeConfigMock->method('getValue')->willReturn('1');

        $this->sut = new Config($scopeConfigMock);
    }

    public function testConfigShouldBeTrue(): void
    {
        $actual = $this->sut->isCustomerMetricsEnabled();
        $expected = true;

        $this->assertEquals($expected, $actual);

        $actual = $this->sut->isOrdersMetricsEnabled();
        $expected = true;

        $this->assertEquals($expected, $actual);
    }
}
