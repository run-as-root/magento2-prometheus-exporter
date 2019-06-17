<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Cms;

use Magento\Cms\Api\Data\PageSearchResultsInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Cms\CmsPagesCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CmsPageCountAggregatorUnitTest extends TestCase
{
    /**
     * @var CmsPagesCountAggregator
     */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        /** @var SearchCriteria | MockObject $searchCriteria */
        $searchCriteria = $this->getMockBuilder(SearchCriteria::class)->disableOriginalConstructor()->getMock();

        /** @var UpdateMetricService | MockObject $updateMetricService */
        $updateMetricService = $this->createMock(UpdateMetricService::class);
        $updateMetricService->expects($this->once())->method('update')->willReturn(true);

        $searchResultInterface = $this->getMockBuilder(PageSearchResultsInterface::class)->getMockForAbstractClass();
        $searchResultInterface->expects($this->once())->method('getTotalCount')->willReturn('10');

        /** @var PageRepositoryInterface | MockObject $cmsRepository */
        $cmsRepository = $this->createMock(PageRepositoryInterface::class);
        $cmsRepository->method('getList')->willReturn($searchResultInterface);

        /** @var SearchCriteriaBuilder |MockObject $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($searchCriteria);

        $this->sut = new CmsPagesCountAggregator(
            $updateMetricService,
            $cmsRepository,
            $searchCriteriaBuilder
        );
    }

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testExecuteReturnPrometheusResult(): void
    {
        $this->assertTrue($this->sut->aggregate());
    }
}
