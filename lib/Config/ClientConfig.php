<?php
declare(strict_types=1);
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Config;

class ClientConfig implements ClientConfigInterface
{
    private bool $isDebugAllowed = false;
    private ?string $serviceUrl = '';
    private ?string $apiKey = '';

    public function setIsDebugAllowed(bool $isDebugAllowed = false): void
    {
        $this->isDebugAllowed = $isDebugAllowed;
    }

    public function isDebugAllowed(): ?bool
    {
        return $this->isDebugAllowed;
    }

    public function setServiceUrl(string $serviceUrl = null): void
    {
        $this->serviceUrl = $serviceUrl;
    }

    public function getServiceUrl(): ?string
    {
        return $this->serviceUrl;
    }

    public function setApiKey(string $apiKey = null): void
    {
        $this->apiKey = $apiKey;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }
}
