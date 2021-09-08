<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes;

use RunAsRoot\NewRelicApi\Request\Metric\Data\AbstractMetric;

class GaugeMetric extends AbstractMetric
{
    private const METRIC_TYPE = 'gauge';

    private float $value;

    public function setValue(float $value): void
    {
        $this->value = $value;
    }

    public function getValue(): float
    {
        return $this->value;
    }

    public function getType(): string
    {
        return self::METRIC_TYPE;
    }
}
