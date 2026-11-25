<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Controller\Adminhtml\Prometheus;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Oauth\Helper\Oauth;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use RunAsRoot\PrometheusExporter\Controller\Adminhtml\Prometheus\GenerateToken;

final class GenerateTokenUnitTest extends TestCase
{
    public function test_it_is_a_backend_action_so_that_admin_authentication_applies(): void
    {
        // Magento only attaches the admin authentication plugin and the ACL check to descendants of
        // \Magento\Backend\App\AbstractAction. A plain framework action in the adminhtml area is reachable anonymously.
        $this->assertTrue(is_subclass_of(GenerateToken::class, AbstractAction::class));
    }

    public function test_it_is_guarded_by_the_acl_resource_of_the_metric_configuration_section(): void
    {
        $this->assertSame(
            'RunAsRoot_PrometheusExporter::config_runasroot_prometheusexporter',
            GenerateToken::ADMIN_RESOURCE
        );
    }

    public function test_it_returns_a_freshly_generated_token_as_json(): void
    {
        /** @var Json|MockObject $jsonResult */
        $jsonResult = $this->createMock(Json::class);
        $jsonResult->expects($this->once())->method('setData')->with(['token' => 'generated-token']);

        /** @var ResultFactory|MockObject $resultFactory */
        $resultFactory = $this->createMock(ResultFactory::class);
        $resultFactory->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_JSON)
            ->willReturn($jsonResult);

        /** @var Context|Stub $context */
        $context = $this->createStub(Context::class);
        $context->method('getResultFactory')->willReturn($resultFactory);

        /** @var Oauth|MockObject $oauthHelper */
        $oauthHelper = $this->createMock(Oauth::class);
        $oauthHelper->expects($this->once())->method('generateTokenSecret')->willReturn('generated-token');

        $sut = new GenerateToken($context, $oauthHelper);

        $this->assertSame($jsonResult, $sut->execute());
    }
}
