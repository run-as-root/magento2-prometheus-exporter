<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes;

use RunAsRoot\NewRelicApi\Request\Metric\Data\AbstractMetric;
use RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes\Value\SummaryMetricValue;
use Symfony\Component\Serializer\Annotation\SerializedName;

class SummaryMetric extends AbstractMetric
{
    private const METRIC_TYPE = 'summary';

    /**
     * @SerializedName("interval.ms")
     */
    private int $intervalMs;
    private SummaryMetricValue $value;

    public function getIntervalMs(): int
    {
        return $this->intervalMs;
    }

    public function setIntervalMs(int $intervalMs): void
    {
        $this->intervalMs = $intervalMs;
    }

    public function getValue(): SummaryMetricValue
    {
        return $this->value;
    }

    public function setValue(SummaryMetricValue $value): void
    {
        $this->value = $value;
    }

    public function getType(): string
    {
        return self::METRIC_TYPE;
    }
}
