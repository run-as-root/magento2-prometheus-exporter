<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Index;

use Magento\Eav\Api\AttributeSetRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class AttributeSetCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_eav_attribute_set_count_total';

    private $updateMetricService;
    private $attributeSetRepository;
    private $searchCriteriaBuilder;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
        AttributeSetRepositoryInterface $attributeSetRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento 2 Eav Attribute Set Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $attributeSets = $this->attributeSetRepository->getList($searchCriteria);

        $this->updateMetricService->update($this->getCode(), (string)$attributeSets->getTotalCount());

        return true;
    }
}
