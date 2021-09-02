<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes;

use RunAsRoot\NewRelicApi\Request\Metric\Data\AbstractMetric;
use Symfony\Component\Serializer\Annotation\SerializedName;

class CountMetric extends AbstractMetric
{
    private const METRIC_TYPE = 'count';

    /**
     * @SerializedName ("interval.ms")
     */
    private int $intervalMs;
    private float $value;

    public function getIntervalMs(): int
    {
        return $this->intervalMs;
    }

    public function setIntervalMs(int $intervalMs): void
    {
        $this->intervalMs = $intervalMs;
    }

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
