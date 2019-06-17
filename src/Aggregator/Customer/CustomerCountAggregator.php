<?php
declare(strict_types=1);
/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Aggregator\Customer;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use RunAsRoot\PrometheusExporter\Api\MetricAggregatorInterface;
use RunAsRoot\PrometheusExporter\Service\UpdateMetricService;

class CustomerCountAggregator implements MetricAggregatorInterface
{

    private const METRIC_CODE = 'magento2_customer_count_total';

    /**
     * @var UpdateMetricService
     */
    private $updateMetricService;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @var CustomerRepositoryInterface
     */
    private $customerRepository;

    public function __construct(
        UpdateMetricService $updateMetricService,
        CustomerRepositoryInterface $customerRepository,
        StoreRepositoryInterface $storeRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        $this->updateMetricService = $updateMetricService;
        $this->customerRepository = $customerRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeRepository = $storeRepository;
    }

    public function getCode(): string
    {
        return self::METRIC_CODE;
    }

    public function getHelp(): string
    {
        return 'Magento2 Customer Count by state';
    }

    public function getType(): string
    {
        return 'gauge';
    }

    /**
     * @throws LocalizedException
     */
    public function aggregate(): bool
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();

        $searchResult = $this->customerRepository->getList($searchCriteria);

        if ($searchResult->getTotalCount() === 0) {
            return true;
        }

        $customers = $searchResult->getItems();

        $countByStore = [];
        foreach ($customers as $customer) {
            $storeId = $customer->getStoreId();

            try {
                $store = $this->storeRepository->getById($storeId);
                $storeCode = $store->getCode();
            } catch (NoSuchEntityException $e) {
                $storeCode = $storeId;
            }

            if (!array_key_exists($storeCode, $countByStore)) {
                $countByStore[$storeCode] = 0;
            }

            $countByStore[$storeCode]++;
        }

        foreach ($countByStore as $storeCode => $count) {
            $labels = ['store_code' => $storeCode];

            $this->updateMetricService->update(self::METRIC_CODE, (string)$count, $labels);
        }

        return true;
    }
}