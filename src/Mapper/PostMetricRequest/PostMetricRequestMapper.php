<?php
declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Mapper\PostMetricRequest;

use RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes\GaugeMetric;
use RunAsRoot\NewRelicApi\Request\Metric\MetricPostRequest;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Data\NewRelicConfig;

class PostMetricRequestMapper
{
    private const INSTANCE_NAME = 'instanceName';

    private NewRelicConfig $newRelicConfig;

    public function __construct(NewRelicConfig $newRelicConfig)
    {
        $this->newRelicConfig = $newRelicConfig;
    }

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
            $gaugeMetric->addAttribute(self::INSTANCE_NAME, $this->newRelicConfig->getInstanceName());

            $metricPostRequest->addMetric($gaugeMetric);
        }

        return $metricPostRequest;
    }
}
