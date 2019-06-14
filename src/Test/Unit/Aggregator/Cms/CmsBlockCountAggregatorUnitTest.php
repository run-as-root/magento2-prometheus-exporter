<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Unit\Aggregator\Cms;

use Magento\Cms\Api\BlockRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Aggregator\Cms\CmsBlocksCountAggregator;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CmsBlockCountAggregatorUnitTest extends TestCase
{
    /**
     * @var CmsBlocksCountAggregator
     */
    private $sut;

    protected function setUp()
    {
        parent::setUp();

        /** @var UpdateMetricService | MockObject $updateMetricService */
        $updateMetricService = $this->createMock(UpdateMetricService::class);
        /** @var BlockRepositoryInterface | MockObject $cmsRepository */
        $cmsRepository = $this->createMock(BlockRepositoryInterface::class);
        /** @var SearchCriteriaBuilder | MockObject $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);

        $this->sut = new CmsBlocksCountAggregator(
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
