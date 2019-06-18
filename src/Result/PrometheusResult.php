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
use RunAsRoot\PrometheusExporter\Data\Config;
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

    /**
     * @var \RunAsRoot\PrometheusExporter\Data\Config
     */
    private $config;

    public function __construct(
        MetricAggregatorPool $metricAggregatorPool,
        MetricRepositoryInterface $metricRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Config $config
    ) {
        $this->metricRepository = $metricRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->metricAggregatorPool = $metricAggregatorPool;
        $this->config = $config;
    }

    protected function render(HttpResponseInterface $response)
    {
        parent::render($response);
        $formattedMetrics = $this->collectMetrics();
        $this->setContents($formattedMetrics);

        $response->setBody($this->contents);
        $response->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);
        $this->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);

        return $this;
    }

    protected function collectMetrics() : string
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchResults = $this->metricRepository->getList($searchCriteria);

        /** @var MetricInterface[] $metrics */
        $metrics = $searchResults->getItems();

        $output = '';
        $enabledMetrics = $this->config->getMetricsStatus();
        $addedMetaData = [];

        foreach ($metrics as $metric) {
            if(!in_array($metric->getCode(), $enabledMetrics, true)) {
                continue;
            }

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

            $help    = "# HELP $code $help" . "\n";
            if (!in_array($help, $addedMetaData, true)) {
                $output .= $help;
                $addedMetaData[] = $help;
            }

            $type    = "# TYPE $code $type" . "\n";
            if (!in_array($type, $addedMetaData, true)) {
                $output .= $type;
                $addedMetaData[] = $type;
            }

            $output .= sprintf('%s{%s} %s', $code, $label, $value) . "\n";
        }

        return $output;
    }
}
