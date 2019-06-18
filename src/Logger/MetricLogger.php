<?php
declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Logger;

use Monolog\Logger;

class MetricLogger extends Logger
{
    public function __construct(MetricsLogHandler $logHandler)
    {
        parent::__construct('rar_prometheus_metric_logger', ['file' => $logHandler]);
    }
}
