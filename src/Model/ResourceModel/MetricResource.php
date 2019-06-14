<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class MetricResource extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('run_as_root_prometheus_metrics', 'id');
    }
}