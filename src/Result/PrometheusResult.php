<?php

declare(strict_types=1);

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Result;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;

class PrometheusResult extends Raw
{
    /**
     * @var MetricRepositoryInterface
     */
    private $metricRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var MetricAggregatorPool
     */
    private $metricAggregatorPool;

    public function __construct(
        MetricAggregatorPool $metricAggregatorPool,
        MetricRepositoryInterface $metricRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->metricRepository = $metricRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->metricAggregatorPool = $metricAggregatorPool;
    }

    protected function render(HttpResponseInterface $response)
    {
        parent::render($response);
        $formatedMetrics = $this->collectMetrics();
        $this->setContents($formatedMetrics);

        $response->setBody($this->contents);
        $response->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);
        $this->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);

        return $this;
    }

    protected function collectMetrics()
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->metricRepository->getList($searchCriteria);

        /** @var MetricInterface[] $metrics */
        $metrics = $searchResults->getItems();

        $output = '';
        foreach ($metrics as $metric) {
            $code = $metric->getCode();

            $metricAggregator = $this->metricAggregatorPool->getByCode($code);

            if ($metricAggregator === null) {
                // @todo log missing metric aggregator or code mismatch
                continue;
            }

            $help = $metricAggregator->getHelp();
            $type = $metricAggregator->getType();
            $value = $metric->getValue();

            $labels = $metric->getLabels();
            $label = '';
            foreach ($labels as $labelName => $labelValue) {
                $label .= sprintf('%s="%s",', $labelName, $labelValue);
            }
            $label = trim($label, ',');

            $output .= "# HELP $code $help" . "\n";
            $output .= "# TYPE $code $type" . "\n";
            $output .= sprintf('%s{%s} %s', $code, $label, $value) . "\n";
        }

        return $output;
    }
}
