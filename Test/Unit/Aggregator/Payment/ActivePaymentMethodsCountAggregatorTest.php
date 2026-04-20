<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Module;

use Magento\Payment\Api\PaymentMethodListInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\StoreRepositoryInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Module\ModuleCountAggregator;
use RunAsRoot\PrometheusExporter\Aggregator\Payment\ActivePaymentMethodsCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

final class ActivePaymentMethodsCountAggregatorTest extends TestCase
{
    /** @var ModuleCountAggregator */
    private $sut;

    /** @var MockObject|UpdateMetricService */
    private $updateMetricService;

    /** @var MockObject|StoreRepositoryInterface */
    private $storeRepository;

    /** @var MockObject|PaymentMethodListInterface */
    private $paymentMethodList;

    protected function setUp(): void
    {
        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->storeRepository     = $this->createMock(StoreRepositoryInterface::class);
        $this->paymentMethodList   = $this->createMock(PaymentMethodListInterface::class);

        $this->sut = new ActivePaymentMethodsCountAggregator(
            $this->updateMetricService,
            $this->storeRepository,
            $this->paymentMethodList
        );
    }

    public function testAggregate(): void
    {
        $store1 = $this->createMock(StoreInterface::class);
        $store2 = $this->createMock(StoreInterface::class);

        $store1
            ->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $store1
            ->expects($this->once())
            ->method('getCode')
            ->willReturn('admin');

        $store2
            ->expects($this->once())
            ->method('getId')
            ->willReturn(2);

        $store2
            ->expects($this->once())
            ->method('getCode')
            ->willReturn('default');

        $this->storeRepository
            ->expects($this->once())
            ->method('getList')
            ->willReturn([$store1, $store2]);

        $paymentMethodCallCount = 0;
        $this->paymentMethodList
            ->expects($this->exactly(2))
            ->method('getActiveList')
            ->willReturnCallback(function (...$args) use (&$paymentMethodCallCount) {
                $paymentMethodCallCount++;
                if ($paymentMethodCallCount === 1) {
                    $expected = [1];
                    $this->assertSame($expected, array_slice($args, 0, count($expected)));
                    return ['a', 'b'];
                }
                if ($paymentMethodCallCount === 2) {
                    $expected = [2];
                    $this->assertSame($expected, array_slice($args, 0, count($expected)));
                    return ['a'];
                }
                return [];
            });

        $labels1 = [
            'store_id' => 1,
            'store_code' => 'admin'
        ];
        $labels2 = [
            'store_id' => 2,
            'store_code' => 'default'
        ];

        $updateCallCount = 0;
        $this->updateMetricService
            ->expects($this->exactly(2))
            ->method('update')
            ->willReturnCallback(function (...$args) use (&$updateCallCount, $labels1, $labels2) {
                $updateCallCount++;
                if ($updateCallCount === 1) {
                    $expected = [
                        'magento_active_payment_methods_count_total',
                        '2',
                        $labels1,
                    ];
                    $this->assertSame($expected, array_slice($args, 0, count($expected)));
                    return true;
                }
                if ($updateCallCount === 2) {
                    $expected = [
                        'magento_active_payment_methods_count_total',
                        '1',
                        $labels2,
                    ];
                    $this->assertSame($expected, array_slice($args, 0, count($expected)));
                    return true;
                }
                return true;
            });

        $this->sut->aggregate();
    }
}
