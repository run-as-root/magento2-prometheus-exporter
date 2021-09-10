<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\NewRelicApi\Metric;

use RunAsRoot\NewRelicApi\Exception\ApiException;
use RunAsRoot\NewRelicApi\Response\Metric\MetricPostResponse;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;

interface MetricNewRelicApiInterface
{
    /**
     * @param MetricInterface[] $metrics
     * @throws ApiException
     */
    public function post(array $metrics): MetricPostResponse;
}
