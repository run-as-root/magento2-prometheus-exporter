<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Service;

interface UpdateMetricServiceInterface
{
    public function update(string $code, string $value, array $labels = []): bool;
}
