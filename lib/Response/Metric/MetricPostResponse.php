<?php
declare(strict_types=1);
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Response\Metric;

use RunAsRoot\NewRelicApi\Response\AbstractResponse;

class MetricPostResponse extends AbstractResponse
{
    private string $requestId;

    public function getRequestId(): string
    {
        return $this->requestId;
    }

    public function setRequestId(string $requestId): void
    {
        $this->requestId = $requestId;
    }
}
