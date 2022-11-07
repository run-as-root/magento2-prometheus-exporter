<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Module;

use Magento\Shipping\Model\Config\Source\Allmethods as ShippingAllMethods;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Shipping\ActiveShippingMethodsCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class ActiveShippingMethodsCountAggregatorTest extends TestCase
{
    /** @var ActiveShippingMethodsCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|ShippingAllMethods */
    private $shippingMethods;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->shippingMethods     = $this->createMock(ShippingAllMethods::class);

        $this->sut = new ActiveShippingMethodsCountAggregator(
            $this->updateMetricService,
            $this->shippingMethods
        );
    }

    public function testAggregate(): void
    {
        $this->shippingMethods
            ->expects($this->once())
            ->method('toOptionArray')
            ->with(...[true])
            ->willReturn(['a', 'b']);

        $this->updateMetricService
            ->expects($this->once())
            ->method('update')
            ->with(...['magento_active_shipping_methods_count_total', '2']);

        $this->sut->aggregate();
    }
}
