<?php
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Config;

interface ClientConfigInterface
{
    public function setIsDebugAllowed(bool $isDebugAllowed = false): void;

    public function isDebugAllowed(): ?bool;

    public function setServiceUrl(?string $serviceUrl = null): void;

    public function getServiceUrl(): ?string;

    public function setApiKey(?string $apiKey = null): void;

    public function getApiKey(): ?string;
}
