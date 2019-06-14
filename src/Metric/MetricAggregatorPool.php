<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Metric;

use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;

class MetricAggregatorPool
{
    /**
     * @var MetricAggregatorInterface[]
     */
    private $items;

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    /**
     * @return MetricAggregatorInterface[]
     */
    public function getItems(): array
    {
        return $this->items;
    }
}
