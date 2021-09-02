<?php
declare(strict_types=1);
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Response;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

abstract class AbstractResponse implements ResponseInterface
{
    private HttpRequestInterface $httpRequest;
    private HttpResponseInterface $httpResponse;

    public function getHttpRequest(): HttpRequestInterface
    {
        return $this->httpRequest;
    }

    public function setHttpRequest(HttpRequestInterface $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    public function getHttpResponse(): HttpResponseInterface
    {
        return $this->httpResponse;
    }

    public function setHttpResponse(HttpResponseInterface $httpResponse)
    {
        $this->httpResponse = $httpResponse;
    }
}
