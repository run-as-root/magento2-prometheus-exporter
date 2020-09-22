<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Eav;

use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResourceConnection;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class AttributeCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento_eav_attribute_count_total';

    private $updateMetricService;

    private $attributeRepository;

    private $searchCriteriaBuilder;

    private $connection;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
        AttributeRepositoryInterface $attributeRepository,
        ResourceConnection $connection,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService   = $updateMetricService;
        $this->attributeRepository   = $attributeRepository;
        $this->connection            = $connection;
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
        $select     = 'SELECT entity_type_code FROM eav_entity_type;';
        $eavTypes   = $this->connection->getConnection()->fetchAll($select);
        $totalCount = 0;

        foreach ($eavTypes as $eavType) {
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $attributes     = $this->attributeRepository->getList($eavType['entity_type_code'], $searchCriteria);
            $totalCount     += $attributes->getTotalCount();
        }

        return $this->updateMetricService->update($this->getCode(), (string)$totalCount);
    }
}
