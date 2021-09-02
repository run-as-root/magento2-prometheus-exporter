<?php
declare(strict_types=1);

/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Request\Metric\Data;

interface MetricInterface
{
    public function getName(): string;

    public function setName(string $name): void;

    public function getTimestamp(): int;

    public function setTimestamp(int $timestamp): void;

    public function getType(): string;

    public function getAttributes(): ?array;

    public function addAttribute(string $key, string $value): void;

    public function setAttributes(?array $attributes): void;

    public function getHeaders(): array;
}
