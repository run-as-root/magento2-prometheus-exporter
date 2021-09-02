<?php
declare(strict_types=1);
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Exception;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Psr\Http\Message\ResponseInterface as HttpResponseInterface;
use Throwable;

class DeserializationFailedException extends \RuntimeException
{
    private HttpRequestInterface $httpRequest;
    private HttpResponseInterface $httpResponse;

    public function __construct(
        HttpRequestInterface $httpRequest,
        HttpResponseInterface $httpResponse,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->httpRequest = $httpRequest;
        $this->httpResponse = $httpResponse;
    }

    public function getHttpRequest(): HttpRequestInterface
    {
        return $this->httpRequest;
    }

    public function getHttpResponse(): HttpResponseInterface
    {
        return $this->httpResponse;
    }
}
