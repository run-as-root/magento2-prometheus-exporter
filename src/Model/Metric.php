<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Model;

use Magento\Framework\Model\AbstractModel;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Model\ResourceModel\MetricCollection;
use RunAsRoot\PrometheusExporter\Model\ResourceModel\MetricResource;

class Metric extends AbstractModel implements MetricInterface
{
    protected function _construct()
    {
        $this->_init(MetricResource::class);
        $this->_setResourceModel(MetricResource::class, MetricCollection::class);
    }

    public function asArray(): array
    {
        return (array)$this->toArray();
    }

    public function getCode(): string
    {
        return (string)$this->getData('code');
    }

    public function setCode(string $code): void
    {
        $this->setData('code', $code);
    }

    public function getLabels(): string
    {
        return (string)$this->getData('labels');
    }

    public function setLabels(string $labels): void
    {
        $this->setData('labels', $labels);
    }

    public function getValue(): string
    {
        return (string)$this->getData('value');
    }

    public function setValue(string $value): void
    {
        $this->setData('value', $value);
    }

}