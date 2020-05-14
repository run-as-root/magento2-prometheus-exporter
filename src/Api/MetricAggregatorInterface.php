<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Api;

interface MetricAggregatorInterface
{
    /**
     * The aggregate function is responsible for collecting data and update the metric records. You can just
     * use the \RunAsRoot\PrometheusExporter\Service\UpdateMetricService to update a new
     * metric record in the database.
     *
     * @return bool
     */
    public function aggregate(): bool;

    /**
     * The metric code is the name of the specific metric.
     * Have a look at the link below to find detailed
     * information about naming conventions.
     *
     * @see https://prometheus.io/docs/practices/naming/
     *
     * @return string
     */
    public function getCode(): string;

    /**
     * Prometheus expects a metric description called HELP. HELP lines may contain any sequence
     * of UTF-8 characters (after the metric name), but the backslash and the line feed
     * characters have to be escaped as \\ and \n, respectively.
     *
     * @see https://prometheus.io/docs/instrumenting/exposition_formats/
     *
     * @return string
     */
    public function getHelp(): string;

    /**
     * Prometheus expects a metric type to be set on each metric.
     * Type can only be one of the following:
     * gauge, counter, summary, histogram, untyped.
     *
     * @see https://prometheus.io/docs/concepts/metric_types/
     *
     * @return string
     */
    public function getType(): string;
}
