<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\NewRelicApi\Metric;

use RunAsRoot\NewRelicApi\Api\Metric\MetricV1Api;
use RunAsRoot\NewRelicApi\Exception\ApiException;
use RunAsRoot\NewRelicApi\Response\Metric\MetricPostResponse;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Exception\PostMetricException;
use RunAsRoot\PrometheusExporter\Mapper\PostMetricRequest\PostMetricRequestMapper;

class MetricNewRelicApi implements MetricNewRelicApiInterface
{
    private MetricV1Api $api;
    private PostMetricRequestMapper $postMetricRequestMapper;

    public function __construct(
        MetricV1Api $api,
        PostMetricRequestMapper $postMetricRequestMapper
    ) {
        $this->api = $api;
        $this->postMetricRequestMapper = $postMetricRequestMapper;
    }

    /**
     * @param MetricInterface[] $metrics
     * @throws ApiException
     */
    public function post(array $metrics): MetricPostResponse
    {
        try {
            $response = $this->api->post($this->postMetricRequestMapper->map($metrics));
        } catch (ApiException $e) {
            throw new PostMetricException($e->getMessage(), 0, $e);
        }

        return $response;
    }
}
