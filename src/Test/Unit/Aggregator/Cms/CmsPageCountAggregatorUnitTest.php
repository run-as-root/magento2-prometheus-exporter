<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Cms;

use Magento\Cms\Api\PageRepositoryInterface;
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

    /** @var UpdateMetricService|MockObject */
    private $updateMetricService;

    /** @var PageRepositoryInterface|MockObject */
    private $cmsRepository;

    /** @var SearchCriteriaBuilder|MockObject */
    private $searchCriteriaBuilder;


    protected function setUp()
    {
        parent::setUp();

        $this->updateMetricService = $this->createMock(UpdateMetricService::class);
        $this->cmsRepository = $this->createMock(PageRepositoryInterface::class);
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);

        $this->sut = new CmsPagesCountAggregator(
            $this->updateMetricService,
            $this->cmsRepository,
            $this->searchCriteriaBuilder
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
