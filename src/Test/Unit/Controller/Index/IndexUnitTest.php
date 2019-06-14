<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Controller\Index;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Controller\Index\Index;
use Magento\Framework\App\Action\Context;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use RunAsRoot\PrometheusExporter\Result\PrometheusResult;
use RunAsRoot\PrometheusExporter\Result\PrometheusResultFactory;

class IndexUnitTest extends TestCase
{
    /**
     * @var Index
     */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        $metricRepositoryMock = $this->createMock(MetricRepository::class);
        $searchResultBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $prometheusResult = new PrometheusResult($metricRepositoryMock, $searchResultBuilder);

        /** @var Context|\PHPUnit_Framework_MockObject_MockObject $scopeConfigMock */
        $contextMock = $this->createMock(Context::class);
        $prometheusResultFactory = $this->createMock(PrometheusResultFactory::class);
        $prometheusResultFactory->method('create')->willReturn($prometheusResult);

        $this->sut = new Index($contextMock, $prometheusResultFactory);
    }

    public function testExecuteReturnPrometheusResult(): void
    {
        $actual = $this->sut->execute();
        $expected = PrometheusResult::class;

        $this->assertInstanceOf($expected, $actual);
    }
}
