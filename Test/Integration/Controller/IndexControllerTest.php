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
    /**
     * @magentoAppArea frontend
     */
    public function testUnauthorizedResponse()
    {
        $this->dispatch('metrics/index/index');
        $this->assertEquals(401, $this->getResponse()->getStatusCode(), 'Status code should be 401 Unauthorized');
        $this->assertEquals(
            'You are not allowed to see these metrics.',
            $this->getResponse()->getBody(),
            'Body should be error message'
        );
    }

    /**
     * @magentoAppArea frontend
     * @magentoConfigFixture current_store metric_configuration/security/token supersecrettokenxxx
     */
    public function testAuthorizedResponse()
    {
        $this->getRequest()->getHeaders()->addHeaderLine('Authorization: Bearer supersecrettokenxxx');
        $this->dispatch('metrics/index/index');
        $this->assertEquals(200, $this->getResponse()->getStatusCode(), 'Status code should be 200 OK');
    }
}
