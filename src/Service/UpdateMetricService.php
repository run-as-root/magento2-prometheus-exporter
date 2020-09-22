<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Service;

use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Model\MetricFactory;

class UpdateMetricService implements UpdateMetricServiceInterface
{
    private $metricRepository;
    private $metricFactory;
    private $logger;

    public function __construct(
        MetricRepositoryInterface $metricRepository,
        MetricFactory $metricFactory,
        LoggerInterface $logger
    ) {
        $this->metricRepository = $metricRepository;
        $this->metricFactory = $metricFactory;
        $this->logger = $logger;
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
            $this->logger->error($e->getMessage());

            return false;
        }

        return true;
    }
}
