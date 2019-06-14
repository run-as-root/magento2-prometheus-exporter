<?php
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Api;

interface MetricAggregatorInterface
{
    public function aggregate(): bool;
}