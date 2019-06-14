<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Repository;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Model\Metric;
use RunAsRoot\PrometheusExporter\Model\MetricFactory;
use RunAsRoot\PrometheusExporter\Model\ResourceModel\MetricCollection;
use RunAsRoot\PrometheusExporter\Model\ResourceModel\MetricCollectionFactory;
use RunAsRoot\PrometheusExporter\Model\ResourceModel\MetricResource;
use RuntimeException;

class MetricRepository implements MetricRepositoryInterface
{
    /**
     * @var MetricFactory
     */
    protected $metricFactory;

    /**
     * @var MetricResource
     */
    private $metricResource;

    /**
     * @var MetricCollectionFactory
     */
    private $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    public function __construct(
        MetricResource $metricResource,
        MetricFactory $metricFactory,
        MetricCollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory
    ) {
        $this->metricFactory = $metricFactory;
        $this->metricResource = $metricResource;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * @param MetricInterface $object
     *
     * @throws CouldNotSaveException
     *
     * @return MetricInterface
     */
    public function save(MetricInterface $object): MetricInterface
    {
        try {
            /* @var Metric $object */
            $this->metricResource->save($object);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__($e->getMessage()));
        }

        return $object;
    }

    /**
     * @param int $id
     *
     * @throws NoSuchEntityException
     *
     * @return MetricInterface
     */
    public function getById(int $id): MetricInterface
    {
        /** @var Metric $object */
        $object = $this->metricFactory->create();
        $this->metricResource->load($object, $id);
        if (!$object->getId()) {
            throw new NoSuchEntityException(__('Metric with id "%1" does not exist.', $id));
        }

        return $object;
    }

    public function getByCode(string $code): MetricInterface
    {
        /** @var Metric $object */
        $object = $this->metricFactory->create();
        $this->metricResource->load($object, $code, 'code');
        if (!$object->getId()) {
            throw new NoSuchEntityException(__('Metric with code "%1" does not exist.', $code));
        }

        return $object;
    }

    public function getByCodeAndLabels(string $code, array $labels): MetricInterface
    {
        $labelsJson = json_encode($labels);

        /** @var MetricCollection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('code', $code);
        $collection->addFieldToFilter('labels', $labelsJson);

        $collection->load();

        if ($collection->count() === 0) {
            throw new NoSuchEntityException(
                __('Metric with code "%1" and labels "%s" does not exist.', $code, $labelsJson)
            );
        }
        if ($collection->count() > 1) {
            throw new RuntimeException(
                sprintf('Found more than one metric for code="%s" and labels="%s"', $code, $labelsJson)
            );
        }

        /** @var MetricInterface $object */
        $object = $collection->getFirstItem();

        return $object;
    }

    /**
     * @param MetricInterface $object
     *
     * @throws CouldNotDeleteException
     *
     * @return bool
     */
    public function delete(MetricInterface $object): bool
    {
        try {
            /* @var Metric $object */
            $this->metricResource->delete($object);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @param int $id
     *
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     *
     * @return bool
     */
    public function deleteById(int $id): bool
    {
        $object = $this->getById($id);

        return $this->delete($object);
    }

    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface
    {
        /** @var MetricCollection $collection */
        $collection = $this->collectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            $fields = [];
            $conditions = [];
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $fields[] = $filter->getField();
                $conditions[] = [$condition => $filter->getValue()];
            }
            if ($fields) {
                $collection->addFieldToFilter($fields, $conditions);
            }
        }
        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $direction = ($sortOrder->getDirection() === SortOrder::SORT_ASC) ? 'ASC' : 'DESC';
                $collection->addOrder($sortOrder->getField(), $direction);
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $objects = [];
        foreach ($collection as $objectModel) {
            $objects[] = $objectModel;
        }

        /** @var SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($objects);

        return $searchResults;
    }
}
