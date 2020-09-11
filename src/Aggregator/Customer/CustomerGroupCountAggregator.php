<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Customer;

use Magento\Customer\Api\GroupRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricServiceInterface;

class CustomerGroupCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento2_customer_group_count_total';

    private $updateMetricService;
    private $searchCriteriaBuilder;
    private $groupRepository;

    public function __construct(
        UpdateMetricServiceInterface $updateMetricService,
        GroupRepositoryInterface $groupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService   = $updateMetricService;
        $this->groupRepository       = $groupRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento2 Customer Group Count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        try {
            $totalCount = $this->groupRepository->getList($searchCriteria)->getTotalCount();
        } catch (LocalizedException $e) {
            $totalCount = -1;
        }

        if ($totalCount <= 0) {
            return false;
        }

        $this->updateMetricService->update(self::METRIC_CODE, (string)$totalCount);

        return true;
    }
}
