<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Logger;

use Magento\Framework\Filesystem\DriverInterface;
use Magento\Framework\Logger\Handler\Base as BaseLogger;

class MetricsLogHandler extends BaseLogger
{
    private const LOG_FILENAME = '/var/log/rar_prometheus_metric.log';

    public function __construct(DriverInterface $filesystem, ?string $filePath = null)
    {
        parent::__construct($filesystem, $filePath, self::LOG_FILENAME);
    }
}
