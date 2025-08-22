<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Result;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\Controller\Result\Raw;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\Api\MetricRepositoryInterface;
use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Metric\MetricAggregatorPool;
use function in_array;

class PrometheusResult extends Raw
{
    private $metricRepository;
    private $searchCriteriaBuilder;
    private $metricAggregatorPool;
    private $config;
    private $sortOrderBuilder;

    public function __construct(
        MetricAggregatorPool $metricAggregatorPool,
        MetricRepositoryInterface $metricRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Config $config,
        ?SortOrderBuilder $sortOrderBuilder = null,
    ) {
        $this->metricRepository = $metricRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->metricAggregatorPool = $metricAggregatorPool;
        $this->config = $config;
        $this->sortOrderBuilder = $sortOrderBuilder ?? ObjectManager::getInstance()->get(SortOrderBuilder::class);
    }

    protected function render(HttpResponseInterface $response)
    {
        parent::render($response);

        $formattedMetrics = $this->collectMetrics();
        $this->setContents($formattedMetrics);

        $response->setBody($this->contents);
        $response->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);
        $this->setHeader('Content-Type', 'text/plain; charset=UTF-8', true);

        return $this;
    }

    protected function collectMetrics(): string
    {
        $searchCriteria = $this->searchCriteriaBuilder->create();
        $searchCriteria->setSortOrders([
            $this->sortOrderBuilder->setField('code')->setDirection(SortOrder::SORT_ASC)->create()
        ]);

        /** @var MetricInterface[] $metrics */
        $metrics = $this->metricRepository->getList($searchCriteria)->getItems();

        $output         = '';
        $enabledMetrics = $this->config->getMetricsStatus();
        $addedMetaData  = [];

        foreach ($metrics as $metric) {
            if (!in_array($metric->getCode(), $enabledMetrics, true)) {
                continue;
            }

            $code = $metric->getCode();

            $metricAggregator = $this->metricAggregatorPool->getByCode($code);

            if ($metricAggregator === null) {
                // @todo log missing metric aggregator or code mismatch
                continue;
            }

            $help  = $metricAggregator->getHelp();
            $type  = $metricAggregator->getType();
            $value = $metric->getValue();

            $labels = $metric->getLabels();
            $label  = '';

            foreach ($labels as $labelName => $labelValue) {
                $label .= sprintf('%s="%s",', $labelName, $labelValue);
            }

            $label = trim($label, ',');

            $help = "# HELP $code $help\n";

            if (!in_array($help, $addedMetaData, true)) {
                $output          .= $help;
                $addedMetaData[] = $help;
            }

            $type = "# TYPE $code $type\n";

            if (!in_array($type, $addedMetaData, true)) {
                $output          .= $type;
                $addedMetaData[] = $type;
            }

            $output .= sprintf('%s{%s} %s', $code, $label, $value) . "\n";
        }

        return $output;
    }
}
