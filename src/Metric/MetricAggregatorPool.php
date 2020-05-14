<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Metric;

use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;

class MetricAggregatorPool
{
    /**
     * @var MetricAggregatorInterface[]
     */
    private $items;

    public function __construct(array $items = [])
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

    public function getByCode(string $code): ?MetricAggregatorInterface
    {
        foreach ($this->items as $aggregator) {
            if ($aggregator->getCode() === $code) {
                return $aggregator;
            }
        }

        return null;
    }
}
