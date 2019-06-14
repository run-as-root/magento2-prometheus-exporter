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
     * @throws CouldNotSaveException
     *
     * @return MetricInterface
     */
    public function save(MetricInterface $metric): MetricInterface;

    /**
     * @param int $id
     *
     * @throws NoSuchEntityException
     *
     * @return MetricInterface
     */
    public function getById(int $id): MetricInterface;

    /**
     * @param string $code
     *
     * @throws NoSuchEntityException
     *
     * @return MetricInterface
     */
    public function getByCode(string $code): MetricInterface;

    /**
     * @param string $code
     * @param array  $labels
     *
     * @throws NoSuchEntityException
     *
     * @return MetricInterface
     */
    public function getByCodeAndLabels(string $code, array $labels): MetricInterface;

    public function getList(SearchCriteriaInterface $criteria): SearchResultsInterface;

    /**
     * @param MetricInterface $metric
     *
     * @throws CouldNotDeleteException
     *
     * @return bool
     */
    public function delete(MetricInterface $metric): bool;

    /**
     * @param int $id
     *
     * @throws CouldNotDeleteException
     *
     * @return bool
     */
    public function deleteById(int $id): bool;
}
