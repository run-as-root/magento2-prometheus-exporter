<?php
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Request;

interface RequestInterface
{
    public function getHeaders(): array;
}
