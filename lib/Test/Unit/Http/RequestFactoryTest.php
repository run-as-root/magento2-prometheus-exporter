<?php
declare(strict_types=1);

/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Http;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;

class RequestFactoryTest extends TestCase
{
    private const METHOD_POST = 'POST';

    /** @var RequestFactory */
    private RequestFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new RequestFactory();
    }

    public function testCreatePostRequestReturnsRequest(): void
    {
        $this->assertInstanceOf(Request::class, $this->factory->createPostRequest('http://example.com'));
    }

    public function testCreatePostRequestUseMethodPost(): void
    {
        $this->assertEquals(
            self::METHOD_POST,
            $this->factory->createPostRequest('http://example.com')->getMethod()
        );
    }

    public function testCreateGetRequestReturnsRequest(): void
    {
        $this->assertInstanceOf(Request::class, $this->factory->createGetRequest('http://example.com'));
    }
}
