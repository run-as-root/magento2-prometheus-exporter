<?php
declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use RunAsRoot\PrometheusExporter\Model\Metric;

class MetricCollection extends AbstractCollection
{
    protected function _construct()
    {
        $this->_init(Metric::class, MetricResource::class);
    }
}
