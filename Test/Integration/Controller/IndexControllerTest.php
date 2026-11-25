<?php
declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Integration\Controller;

use Magento\TestFramework\TestCase\AbstractController;
use Magento\Framework\App\Request;
use Magento\Framework\App\Response;

/**
 * @method Request\Http getRequest()
 * @method Response\Http getResponse()
 */
class IndexControllerTest extends AbstractController
{
    private const UNAUTHORIZED_BODY = 'You are not allowed to see these metrics.';

    /**
     * Token authorization is the shipped default (etc/config.xml): with nothing configured at all,
     * nobody may read the metrics.
     *
     * @magentoAppArea frontend
     */
    public function testUnauthorizedResponseWithDefaultConfiguration()
    {
        $this->dispatch('metrics/index/index');
        $this->assertEquals(401, $this->getResponse()->getStatusCode(), 'Status code should be 401 Unauthorized');
        $this->assertEquals(self::UNAUTHORIZED_BODY, $this->getResponse()->getBody(), 'Body should be error message');
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store metric_configuration/security/enable_token 1
     */
    public function testUnauthorizedResponse()
    {
        $this->dispatch('metrics/index/index');
        $this->assertEquals(401, $this->getResponse()->getStatusCode(), 'Status code should be 401 Unauthorized');
        $this->assertEquals(
            self::UNAUTHORIZED_BODY,
            $this->getResponse()->getBody(),
            'Body should be error message'
        );
    }

    /**
     * No token saved yet: a header that equals "Bearer " followed by the (empty) token must not open the endpoint.
     *
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store metric_configuration/security/enable_token 1
     */
    public function testUnauthorizedResponseForEmptyBearerWhileNoTokenIsConfigured()
    {
        $this->getRequest()->getHeaders()->addHeaderLine('Authorization: Bearer ');
        $this->dispatch('metrics/index/index');
        $this->assertEquals(401, $this->getResponse()->getStatusCode(), 'Status code should be 401 Unauthorized');
        $this->assertEquals(self::UNAUTHORIZED_BODY, $this->getResponse()->getBody(), 'Body should be error message');
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store metric_configuration/security/enable_token 1
     * @magentoConfigFixture current_store metric_configuration/security/token supersecrettokenxxx
     */
    public function testUnauthorizedResponseForWrongToken()
    {
        $this->getRequest()->getHeaders()->addHeaderLine('Authorization: Bearer supersecrettokenxxy');
        $this->dispatch('metrics/index/index');
        $this->assertEquals(401, $this->getResponse()->getStatusCode(), 'Status code should be 401 Unauthorized');
        $this->assertEquals(self::UNAUTHORIZED_BODY, $this->getResponse()->getBody(), 'Body should be error message');
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store metric_configuration/security/enable_token 1
     * @magentoConfigFixture current_store metric_configuration/security/token supersecrettokenxxx
     */
    public function testAuthorizedResponse()
    {
        $this->getRequest()->getHeaders()->addHeaderLine('Authorization: Bearer supersecrettokenxxx');
        $this->dispatch('metrics/index/index');
        $this->assertEquals(200, $this->getResponse()->getStatusCode(), 'Status code should be 200 OK');
    }

    /**
     * Anonymous access stays possible, but only as an explicit opt-out.
     *
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store metric_configuration/security/enable_token 0
     */
    public function testAnonymousResponseWhenTokenAuthorizationIsDisabled()
    {
        $this->dispatch('metrics/index/index');
        $this->assertEquals(200, $this->getResponse()->getStatusCode(), 'Status code should be 200 OK');
        $this->assertNotEquals(self::UNAUTHORIZED_BODY, $this->getResponse()->getBody());
    }
}
