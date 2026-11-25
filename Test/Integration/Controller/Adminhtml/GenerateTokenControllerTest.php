<?php
declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Integration\Controller\Adminhtml;

use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\TestFramework\TestCase\AbstractBackendController;

/**
 * The inherited testAclHasAccess() / testAclNoAccess() cover the ACL resource of the action.
 *
 * @magentoAppArea adminhtml
 */
class GenerateTokenControllerTest extends AbstractBackendController
{
    /**
     * @var string
     */
    protected $resource = 'RunAsRoot_PrometheusExporter::config_runasroot_prometheusexporter';

    /**
     * @var string
     */
    protected $uri = 'backend/prometheus/prometheus/generatetoken';

    /**
     * @var string
     */
    protected $httpMethod = HttpRequest::METHOD_POST;

    public function testLoggedInAdminReceivesGeneratedToken()
    {
        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);

        $this->assertEquals(200, $this->getResponse()->getHttpResponseCode());
        $payload = json_decode($this->getResponse()->getBody(), true);
        $this->assertIsArray($payload, 'Response should be JSON');
        $this->assertMatchesRegularExpression('/^[a-z0-9]{32}$/', $payload['token'] ?? '');
    }

    public function testAnonymousVisitorDoesNotReachTheAction()
    {
        $this->_auth->logout();

        $this->getRequest()->setMethod($this->httpMethod);
        $this->dispatch($this->uri);

        $this->assertNotEquals('generatetoken', $this->getRequest()->getActionName());
        $this->assertDoesNotMatchRegularExpression('/"token"\s*:\s*"[a-z0-9]+"/', $this->getResponse()->getBody());
    }
}
