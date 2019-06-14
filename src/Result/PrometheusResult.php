<?php

declare(strict_types=1);

/**
 * @copyright see PROJECT_LICENSE.txt
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Result;

use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use \Magento\Framework\Controller\Result\Raw;
use \RunAsRoot\PrometheusExporter\Repository\MetricRepository;
use \Magento\Framework\Api\SearchCriteriaBuilder;

class PrometheusResult extends Raw
{
    /**
     * @var MetricRepository
     */
    private $metricRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    public function __construct(MetricRepository $metricRepository, SearchCriteriaBuilder $searchCriteriaBuilder)
    {
        $this->metricRepository      = $metricRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    protected function render(HttpResponseInterface $response)
    {
        parent::render($response);
        $formatedMetrics = $this->collectMetrics();
        $this->setContents($formatedMetrics);

        $response->setBody($this->contents);
        $response->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);
        $this->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);

        return $this;
    }

    protected function collectMetrics(): string
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $metrics        = $this->metricRepository->getList($searchCriteria);

        $formatedMetrics = '';
        foreach ($metrics as $metric) {
        }

        return $formatedMetrics;
    }
}
