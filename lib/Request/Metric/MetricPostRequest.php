<?php
declare(strict_types=1);

/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Request\Metric;

use RunAsRoot\NewRelicApi\Request\Metric\Data\Metric;
use RunAsRoot\NewRelicApi\Request\Metric\Data\MetricInterface;
use RunAsRoot\NewRelicApi\Request\RequestInterface;

class MetricPostRequest implements RequestInterface
{
    /** @var MetricInterface[]  */
    private array $metrics;

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function addMetric(MetricInterface $metric): void
    {
        $this->metrics[] = $metric;
    }

    public function setMetrics(array $metrics): void
    {
        $this->metrics = $metrics;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
