<?php
declare(strict_types=1);

namespace RunAsRoot\NewRelicApi\Http;

use GuzzleHttp\Psr7\Request;

class RequestFactory
{
    private const METHOD_POST = 'POST';
    private const METHOD_GET = 'GET';

    public function createPostRequest(
        string $uri,
        array $headers = [],
        string $body = null,
        string $version = '1.1'
    ): Request {
        return new Request(self::METHOD_POST, $uri, $headers, $body, $version);
    }

    public function createGetRequest(
        string $uri,
        array $headers = [],
        string $body = null,
        string $version = '1.1'
    ): Request {
        return new Request(self::METHOD_GET, $uri, $headers, $body, $version);
    }
}
