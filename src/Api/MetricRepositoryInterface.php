<?php
declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;

interface MetricRepositoryInterface
{
    /**
     * @param MetricInterface $metric
     *
     * @return MetricInterface
     * @throws CouldNotSaveException
     */
    public function save(MetricInterface $metric): MetricInterface;

    /**
     * @param int $id
     *
     * @return MetricInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $id): MetricInterface;


    /**
     * @param string $code
     *
     * @return MetricInterface
     * @throws NoSuchEntityException
     */
    public function getByCode(string $code): MetricInterface;

    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;

    /**
     * @param MetricInterface $metric
     *
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(MetricInterface $metric): bool;

    /**
     * @param int $id
     *
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $id): bool;
}
