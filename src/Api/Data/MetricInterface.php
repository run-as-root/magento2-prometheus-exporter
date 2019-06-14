<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Api\Data;

interface MetricInterface
{
    public function getId();

    public function getCode(): string;

    public function setCode(string $code): void;

    public function getLabels(): string;

    public function setLabels(string $labels): void;

    public function getValue(): string;

    public function setValue(string $value): void;

}