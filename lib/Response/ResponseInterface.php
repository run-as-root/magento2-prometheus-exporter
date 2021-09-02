<?php
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Response;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;

interface ResponseInterface
{
    public function getHttpRequest(): HttpRequestInterface;

    public function setHttpRequest(HttpRequestInterface $httpRequest);

    public function getHttpResponse(): HttpResponseInterface;

    public function setHttpResponse(HttpResponseInterface $httpResponse);
}
