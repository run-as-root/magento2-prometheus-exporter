<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MetricResource extends AbstractDb
{
    private const TABLE_NAME = 'run_as_root_prometheus_metrics';
    private const INCREMENT_FIELD_NAME = 'id';

    protected function _construct()
    {
        $this->_init(self::TABLE_NAME, self::INCREMENT_FIELD_NAME);
    }
}
