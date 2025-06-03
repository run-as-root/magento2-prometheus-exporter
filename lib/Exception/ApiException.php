<?php
declare(strict_types=1);
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Exception;

use Psr\Http\Message\RequestInterface as HttpRequestInterface;
use Throwable;

abstract class ApiException extends \RuntimeException
{
    private HttpRequestInterface $httpRequest;

    public function __construct(
        HttpRequestInterface $httpRequest,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->httpRequest = $httpRequest;
    }

    public function getHttpRequest(): HttpRequestInterface
    {
        return $this->httpRequest;
    }
}
