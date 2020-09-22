<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Aggregator\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CustomerAddressesCountAggregator implements MetricAggregatorInterface
{
    private const METRIC_CODE = 'magento2_customer_addresses_count_total';

    private $updateMetricService;

    private $searchCriteriaBuilder;

    private $customerRepository;

    public function __construct(
        UpdateMetricService $updateMetricService,
        CustomerRepositoryInterface $customerRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService   = $updateMetricService;
        $this->customerRepository    = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento2 Customer Addresses count';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    public function aggregate(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        try {
            $searchResult = $this->customerRepository->getList($searchCriteria);
            $customers    = $searchResult->getItems();
        } catch (LocalizedException $e) {
            $customers = [];
        }

        $allAddressesCount = 0;

        foreach ($customers as $customer) {
            $addresses = $customer->getAddresses();

            // $addresses could be null
            if ($addresses) {
                $addressesCount    = count($addresses);
                $allAddressesCount += $addressesCount;
            }
        }

        return $this->updateMetricService->update(self::METRIC_CODE, (string)$allAddressesCount);
    }
}
