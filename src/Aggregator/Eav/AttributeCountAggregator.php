<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Index;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class AttributeCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_eav_attribute_count_total';

    private $updateMetricService;
    private $attributeRepository;
    private $searchCriteriaBuilder;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
        AttributeRepositoryInterface $attributeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService   = $updateMetricService;
        $this->attributeRepository   = $attributeRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Eav Attributes Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        return true;
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributes = $this->attributeRepository->getList($searchCriteria);

        $this->updateMetricService->update($this->getCode(), (string)$attributeSets->getTotalCount());

        return true;
    }
}
