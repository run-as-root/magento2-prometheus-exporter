<?php
declare(strict_types=1);
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Api\Metric;

use GuzzleHttp\Exception\GuzzleException;
use RunAsRoot\NewRelicApi\Request\Metric\MetricPostRequest;
use RunAsRoot\NewRelicApi\Response\Metric\MetricPostResponse;

interface MetricV1ApiInterface
{
    /**
     * @throws GuzzleException
     */
    public function post(MetricPostRequest $request): MetricPostResponse;
}
