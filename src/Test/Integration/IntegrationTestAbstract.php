<?php
declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Integration;

use Magento\Framework\ObjectManagerInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;

abstract class IntegrationTestAbstract extends TestCase
{
    /** @var ObjectManagerInterface */
    protected $objectManager;

    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = Bootstrap::getObjectManager();
    }
}
