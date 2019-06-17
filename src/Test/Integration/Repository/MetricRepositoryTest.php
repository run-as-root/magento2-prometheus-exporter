<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Integration\Repository;

use Eurotext\TranslationManager\Api\Data\ProjectInterface;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Model\Metric;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use RunAsRoot\PrometheusExporter\Test\Integration\IntegrationTestAbstract;

class MetricRepositoryTest extends IntegrationTestAbstract
{
    /** @var MetricRepository */
    private $sut;

    protected function setUp()
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
        $labels = ['labelOne' => 'one'];
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
        $labels = ['labelOne' => 'one'];
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
        ];

        foreach ($metrics as $metric) {
            $this->createMetric($metric['code'], $metric['value'], $metric['labels']);
        }

        /** @var SearchCriteria $searchCriteria */
        $searchCriteria = $this->objectManager->get(SearchCriteria::class);

        $searchResults = $this->sut->getList($searchCriteria);

        /** @var $items MetricInterface[] */
        $items = $searchResults->getItems();

        foreach ($metrics as $metric) {
            $isMetricInResult = false;
            $metricCode = $metric['code'];
            foreach ($items as $item) {
                $isMetricInResult = $item->getCode() === $metricCode;
                if ($isMetricInResult === true) {
                    break;
                }
            }
            $this->assertTrue($isMetricInResult, "metric $metricCode was not returned by the repository");
        }
    }

    private function createMetric(string $code, string $value, array $labels = []): Metric
    {
        /** @var Metric $metric */
        $metric = $this->objectManager->get(Metric::class);

        $metric->setCode($code);
        $metric->setValue($value);
        $metric->setLabels($labels);

        $metric->save();

        return $metric;
    }
}
