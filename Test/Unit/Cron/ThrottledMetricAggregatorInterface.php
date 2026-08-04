<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Test\Unit\Cron;

use RunAsRoot\PrometheusExporter\Api\IntervalAwareMetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;

/**
 * Test double for an aggregator that opts into interval throttling.
 *
 * PHPUnit's createMockForIntersectionOfInterfaces() would express this inline, but it only exists
 * from PHPUnit 10 onwards and this module still supports PHPUnit 9.5 (Magento 2.4.6 / 2.4.7).
 */
interface ThrottledMetricAggregatorInterface extends
    MetricAggregatorInterface,
    IntervalAwareMetricAggregatorInterface
{
}
