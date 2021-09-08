<?php
declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Mapper\PostMetricRequest;

use RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes\GaugeMetric;
use RunAsRoot\NewRelicApi\Request\Metric\MetricPostRequest;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;

class PostMetricRequestMapper
{
    /**
     * @param MetricInterface[] $metrics
     */
    public function map(array $metrics): MetricPostRequest
    {
        $metricPostRequest = new MetricPostRequest();

        foreach ($metrics as $metric) {
            $gaugeMetric = new GaugeMetric();
            $gaugeMetric->setName($metric->getCode());
            $gaugeMetric->setValue((float)$metric->getValue());
            $gaugeMetric->setTimestamp(time());
            $gaugeMetric->setAttributes(
                !empty($metric->getLabels()) ? $metric->getLabels() : null
            );

            $metricPostRequest->addMetric($gaugeMetric);
        }

        return $metricPostRequest;
    }
}
