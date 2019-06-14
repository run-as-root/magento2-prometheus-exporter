<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Service;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Model\MetricFactory;

class UpdateMetricService
{
    /**
     * @var MetricRepositoryInterface
     */
    private $metricRepository;

    /**
     * @var MetricFactory
     */
    private $metricFactory;

    public function __construct(
        MetricRepositoryInterface $metricRepository,
        MetricFactory $metricFactory
    ) {
        $this->metricRepository = $metricRepository;
        $this->metricFactory = $metricFactory;
    }

    public function update(string $code, string $value, array $labels = []): bool
    {
        try {
            $metric = $this->metricRepository->getByCodeAndLabels($code, $labels);
        } catch (NoSuchEntityException $e) {
            /** @var MetricInterface $metric */
            $metric = $this->metricFactory->create();
        }

        $metric->setCode($code);
        $metric->setValue($value);
        $metric->setLabels($labels);

        try {
            $this->metricRepository->save($metric);
        } catch (CouldNotSaveException $e) {
            // @todo log exception
            return false;
        }

        return true;
    }
}