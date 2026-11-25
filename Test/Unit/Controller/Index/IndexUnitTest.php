<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Controller\Index\Index;
use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Result\PrometheusResult;
use RunAsRoot\PrometheusExporter\Result\PrometheusResultFactory;

final class IndexUnitTest extends TestCase
{
    private const TOKEN = 'supersecrettokenxxx';

    /** @var Config|Stub */
    private $config;

    /** @var HttpRequest|Stub */
    private $request;

    /** @var ResultFactory|MockObject */
    private $resultFactory;

    /** @var PrometheusResult|Stub */
    private $prometheusResult;

    /** @var PrometheusResultFactory|MockObject */
    private $prometheusResultFactory;

    private Index $sut;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = $this->createStub(Config::class);
        $this->request = $this->createStub(HttpRequest::class);
        $this->resultFactory = $this->createMock(ResultFactory::class);
        $this->prometheusResult = $this->createStub(PrometheusResult::class);
        $this->prometheusResultFactory = $this->createMock(PrometheusResultFactory::class);

        /** @var Context|Stub $context */
        $context = $this->createStub(Context::class);
        $context->method('getRequest')->willReturn($this->request);
        $context->method('getResultFactory')->willReturn($this->resultFactory);

        $this->sut = new Index($context, $this->prometheusResultFactory, $this->config);
    }

    public function test_it_serves_the_metrics_without_a_token_when_token_validation_is_switched_off(): void
    {
        $this->config->method('getTokenValidationEnabled')->willReturn(false);
        $this->config->method('getToken')->willReturn(self::TOKEN);
        $this->givenAuthorizationHeader(false);

        $this->resultFactory->expects($this->never())->method('create');
        $this->prometheusResultFactory->expects($this->once())->method('create')->willReturn($this->prometheusResult);

        $this->assertSame($this->prometheusResult, $this->sut->execute());
    }

    public function test_it_serves_the_metrics_for_the_configured_bearer_token(): void
    {
        $this->config->method('getTokenValidationEnabled')->willReturn(true);
        $this->config->method('getToken')->willReturn(self::TOKEN);
        $this->givenAuthorizationHeader('Bearer ' . self::TOKEN);

        $this->resultFactory->expects($this->never())->method('create');
        $this->prometheusResultFactory->expects($this->once())->method('create')->willReturn($this->prometheusResult);

        $this->assertSame($this->prometheusResult, $this->sut->execute());
    }

    public function test_it_rejects_a_request_without_an_authorization_header(): void
    {
        $this->assertRejected(self::TOKEN, false);
    }

    public function test_it_rejects_an_empty_authorization_header(): void
    {
        $this->assertRejected(self::TOKEN, '');
    }

    public function test_it_rejects_a_wrong_token(): void
    {
        $this->assertRejected(self::TOKEN, 'Bearer not-the-token');
    }

    public function test_it_rejects_a_token_that_is_only_a_prefix_of_the_configured_one(): void
    {
        $this->assertRejected(self::TOKEN, 'Bearer supersecrettoken');
    }

    public function test_it_rejects_a_token_that_only_starts_with_the_configured_one(): void
    {
        $this->assertRejected(self::TOKEN, 'Bearer ' . self::TOKEN . 'x');
    }

    public function test_it_rejects_the_token_without_the_bearer_scheme(): void
    {
        $this->assertRejected(self::TOKEN, self::TOKEN);
    }

    public function test_it_rejects_the_token_with_another_scheme(): void
    {
        $this->assertRejected(self::TOKEN, 'Basic ' . self::TOKEN);
    }

    // Token authorization is the default, so "enabled, but no token generated yet" is the state of every fresh
    // install. It has to stay closed - also for a header that equals 'Bearer ' followed by the empty token.

    public function test_it_rejects_every_request_while_no_token_is_configured(): void
    {
        $this->assertRejected('', false);
    }

    public function test_it_rejects_a_bare_bearer_scheme_while_no_token_is_configured(): void
    {
        $this->assertRejected('', 'Bearer');
    }

    public function test_it_rejects_an_empty_bearer_token_while_no_token_is_configured(): void
    {
        $this->assertRejected('', 'Bearer ');
    }

    public function test_it_rejects_a_blank_bearer_token_while_only_a_blank_token_is_configured(): void
    {
        $this->assertRejected('   ', 'Bearer    ');
    }

    /**
     * Not a data provider on purpose: the suite has to run on PHPUnit 9 (annotations only) up to 12 (attributes only).
     *
     * @param string|false $authorizationHeader
     */
    private function assertRejected(string $configuredToken, $authorizationHeader): void
    {
        $this->config->method('getTokenValidationEnabled')->willReturn(true);
        $this->config->method('getToken')->willReturn($configuredToken);
        $this->givenAuthorizationHeader($authorizationHeader);

        /** @var Raw|MockObject $unauthorizedResult */
        $unauthorizedResult = $this->createMock(Raw::class);
        $unauthorizedResult->expects($this->once())->method('setHttpResponseCode')->with(401);
        $unauthorizedResult->expects($this->once())
            ->method('setContents')
            ->with('You are not allowed to see these metrics.');

        $this->resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_RAW)
            ->willReturn($unauthorizedResult);
        $this->prometheusResultFactory->expects($this->never())->method('create');

        $this->assertSame($unauthorizedResult, $this->sut->execute());
    }

    /**
     * Mirrors Request::getHeader(): the field value for the Authorization header, false when it was not sent.
     *
     * @param string|false $value
     */
    private function givenAuthorizationHeader($value): void
    {
        $this->request->method('getHeader')->willReturnCallback(
            static function (string $name) use ($value) {
                return $name === 'Authorization' ? $value : false;
            }
        );
    }
}
