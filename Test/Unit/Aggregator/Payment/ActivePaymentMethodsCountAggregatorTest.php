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

        $this->paymentMethodList
            ->expects($this->at(0))
            ->method('getActiveList')
            ->with(...[1])
            ->willReturn(['a', 'b']);

        $this->paymentMethodList
            ->expects($this->at(1))
            ->method('getActiveList')
            ->with(...[2])
            ->willReturn(['a']);

        $labels1 = [
            'store_id' => 1,
            'store_code' => 'admin'
        ];
        $labels2 = [
            'store_id' => 2,
            'store_code' => 'default'
        ];

        $this->updateMetricService
            ->expects($this->at(0))
            ->method('update')
            ->with(
                ...[
                       'magento_active_payment_methods_count_total',
                       '2',
                       $labels1
                   ]
            );

        $this->updateMetricService
            ->expects($this->at(1))
            ->method('update')
            ->with(
                ...[
                       'magento_active_payment_methods_count_total',
                       '1',
                       $labels2
                   ]
            );

        $this->sut->aggregate();
    }
}
