<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Test\Integration\Repository;

use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use RunAsRoot\PrometheusExporter\Test\Integration\IntegrationTestAbstract;

class MetricRepositoryTest extends IntegrationTestAbstract
{
    public function testItShouldGetInstance(): void
    {
        $sut = $this->objectManager->create(MetricRepositoryInterface::class);

        $this->assertInstanceOf(MetricRepository::class, $sut);
    }
}