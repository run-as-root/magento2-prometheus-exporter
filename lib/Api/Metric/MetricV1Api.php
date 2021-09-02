<?php
declare(strict_types=1);
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Api\Metric;

use GuzzleHttp\Exception\GuzzleException;
use RunAsRoot\NewRelicApi\Api\AbstractV1Api;
use RunAsRoot\NewRelicApi\Request\Metric\MetricPostRequest;
use RunAsRoot\NewRelicApi\Response\Metric\MetricPostResponse;

class MetricV1Api extends AbstractV1Api implements MetricV1ApiInterface
{
    private const API_END_POINT_FOR_METRIC = '/v1';

    /**
     * @throws GuzzleException
     */
    public function post(MetricPostRequest $request): MetricPostResponse
    {
        $body = $this->serializer->serialize([$request], 'json', $this->getSerializerContext());

        /** @var MetricPostResponse $response */
        $response = $this->sendRequestAndHandleResponse(
            $this->createHttpPostRequest(self::API_END_POINT_FOR_METRIC, $request->getHeaders(), $body),
            $this->createHttpClientOptions(),
            MetricPostResponse::class
        );
        return $response;
    }
}
