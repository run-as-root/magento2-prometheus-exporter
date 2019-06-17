<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Cms;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Cms\Api\Data\BlockSearchResultsInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Cms\CmsBlockCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CmsBlockCountAggregatorUnitTest extends TestCase
{
    /**
     * @var CmsBlockCountAggregator
     */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        /** @var SearchCriteria | MockObject $searchCriteria */
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock();

        /** @var UpdateMetricService | MockObject $updateMetricService */
        $updateMetricService = $this->getMockBuilder(UpdateMetricService::class)->disableOriginalConstructor()->getMock();
        $updateMetricService->method('update')->willReturn(true);

        $searchResultInterface = $this->getMockBuilder(BlockSearchResultsInterface::class)->getMockForAbstractClass();
        $searchResultInterface->expects($this->once())->method('getTotalCount')->willReturn('10');

        /** @var BlockRepositoryInterface | MockObject $cmsRepository */
        $cmsRepository = $this->getMockBuilder(BlockRepositoryInterface::class)->getMockForAbstractClass();
        $cmsRepository->method('getList')->willReturn($searchResultInterface);

        /** @var SearchCriteriaBuilder | MockObject $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->getMockBuilder(SearchCriteriaBuilder::class)->disableOriginalConstructor()->getMock();
        $searchCriteriaBuilder->method('create')->willReturn($searchCriteria);

        $this->sut = new CmsBlockCountAggregator(
            $updateMetricService,
            $cmsRepository,
            $searchCriteriaBuilder
        );
    }

    public function testExecuteReturnPrometheusResult(): void
    {
        $this->assertTrue($this->sut->aggregate());
    }
}
