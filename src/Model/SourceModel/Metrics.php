<?php

declare(strict_types = 1);
/**
 * @copyright see PROJECT_LICENSE.txt
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Model\SourceModel;

use Magento\Framework\Data\OptionSourceInterface;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;

class Metrics implements OptionSourceInterface
{
    private $aggregatorPool;

    public function __construct(MetricAggregatorPool $aggregatorPool)
    {
        $this->aggregatorPool = $aggregatorPool;
    }

    public function toOptionArray() : array
    {
        $pool    = $this->aggregatorPool->getItems();
        $options = [];

        foreach ($pool as $poolItem) {
            $options[] = [
                'value' => $poolItem->getCode(),
                'label' => $poolItem->getCode(),
            ];
        }

        return $options;
    }
}
