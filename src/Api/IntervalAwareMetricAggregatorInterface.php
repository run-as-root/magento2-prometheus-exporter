<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Api;

interface IntervalAwareMetricAggregatorInterface
{
    /**
     * Aggregators computing expensive queries (e.g. full table scans) can implement this
     * to be skipped by AggregateMetricsCron until at least this many seconds have passed
     * since their last successful run, instead of running on every cron tick.
     *
     * @return int
     */
    public function getMinIntervalInSeconds(): int;
}
