<?php
declare(strict_types=1);

/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Request\Metric\Data;

abstract class AbstractMetric implements MetricInterface
{
    private const DEFAULT_TYPE = 'guage';

    private string $name;
    private int $timestamp;
    private ?array $attributes;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function setTimestamp(int $timestamp): void
    {
        $this->timestamp = $timestamp;
    }

    public function getType(): string
    {
        return self::DEFAULT_TYPE;
    }

    public function getAttributes(): ?array
    {
        return  $this->attributes;
    }

    /**
     * @param string $key
     * @param string|bool|int|float $value
     */
    public function addAttribute(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function setAttributes(?array $attributes): void
    {
        $this->attributes = $attributes;
    }

    public function getHeaders(): array
    {
        return [];
    }
}
