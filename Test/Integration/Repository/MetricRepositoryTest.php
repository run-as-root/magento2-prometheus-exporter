<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Integration\Repository;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Model\Metric;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use RunAsRoot\PrometheusExporter\Test\Integration\IntegrationTestAbstract;

final class MetricRepositoryTest extends IntegrationTestAbstract
{
    /** @var MetricRepository */
    private $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sut = $this->objectManager->create(MetricRepositoryInterface::class);
    }

    public function testItShouldGetInstance(): void
    {
        $sut = $this->objectManager->create(MetricRepositoryInterface::class);

        $this->assertInstanceOf(MetricRepository::class, $sut);
    }

    /**
     * @throws NoSuchEntityException
     */
    public function testItShouldCreateAMetricAndGetItById(): void
    {
        $code = 'test_integration_metric_1';
        $value = '47.11';
        $labels = [ 'labelOne' => 'one' ];
        $metric = $this->createMetric($code, $value, $labels);

        $id = (int)$metric->getId();

        $this->assertTrue($id > 0);
        $this->assertEquals($code, $metric->getCode());

        $metricGet = $this->sut->getById($id);

        $this->assertEquals($id, $metricGet->getId());
        $this->assertEquals($code, $metricGet->getCode());
        $this->assertEquals($labels, $metricGet->getLabels());
    }

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function testItShouldDeleteMetrics(): void
    {
        $code = 'test_integration_metric_1';
        $value = '47.11';
        $labels = [ 'labelOne' => 'one' ];
        $metric = $this->createMetric($code, $value, $labels);

        $id = (int)$metric->getId();

        $result = $this->sut->deleteById($id);

        $this->assertTrue($result);

        try {
            $metricGet = $this->sut->getById($id);
        } catch (NoSuchEntityException $e) {
            $metricGet = null;
        }

        $this->assertNull($metricGet);
    }

    public function testItShouldReturnAListOfMetrics(): void
    {
        $metrics = [
            [
                'code' => 'test_integration_metric_1',
                'value' => '47.11',
                'labels' => ['labelOne' => 'one'],
            ],
            [
                'code' => 'test_integration_metric_2',
                'value' => '9999.99',
                'labels' => ['labelOne' => 'two'],
            ],
        ];

        foreach ($metrics as $metric) {
            $this->createMetric($metric['code'], $metric['value'], $metric['labels']);
        }

        sleep(1); // wait a second so repository getList will return something

        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->objectManager->create(SearchCriteria::class);

        $searchResults = $this->sut->getList($searchCriteria);

        $this->assertGreaterThanOrEqual(2, $searchResults->getTotalCount());

        /** @var MetricInterface[] $items */
        $items = $searchResults->getItems();

        foreach ($metrics as $metric) {
            $isMetricInResult = false;
            $metricCode = $metric['code'];

            foreach ($items as $item) {
                if ($item->getCode() !== $metricCode) {
                    continue;
                }

                $isMetricInResult = true;
            }

            $this->assertTrue($isMetricInResult, "metric $metricCode was not returned by the repository");
        }
    }

    private function createMetric(string $code, string $value, array $labels = []): Metric
    {
        /** @var Metric $metric */
        $metric = $this->objectManager->create(Metric::class);

        $metric->setCode($code);
        $metric->setValue($value);
        $metric->setLabels($labels);

        $metric->save();

        return $metric;
    }
}
