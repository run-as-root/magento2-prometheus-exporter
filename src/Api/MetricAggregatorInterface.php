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

    /**
     * Prometheus expects a metric description called HELP. HELP lines may contain any sequence
     * of UTF-8 characters (after the metric name), but the backslash and the line feed
     * characters have to be escaped as \\ and \n, respectively.
     *
     * @see https://prometheus.io/docs/instrumenting/exposition_formats/
     *
     * @return string
     */
    public function getHelp() : string;

    /**
     * Prometheus expects a metric type to be set on each metric.
     * Type can only be one of the following:
     * gauge, counter, summary, histogram, untyped.
     *
     * @see https://prometheus.io/docs/concepts/metric_types/
     *
     * @return string
     */
    public function getType() : string;
}
