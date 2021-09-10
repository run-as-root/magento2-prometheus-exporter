<?php
declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\NewRelicApi\Metric;

use Magento\Framework\ObjectManagerInterface;
use RunAsRoot\NewRelicApi\Api\Metric\MetricV1Api;
use RunAsRoot\NewRelicApi\Exception\ApiException;
use RunAsRoot\NewRelicApi\Response\Metric\MetricPostResponse;
use RunAsRoot\PrometheusExporter\Api\Data\MetricInterface;
use RunAsRoot\PrometheusExporter\NewRelicApi\ApiBuilder;

class MetricNewRelicApiProxy implements MetricNewRelicApiInterface
{
    private ObjectManagerInterface $objectManager;
    private ApiBuilder $apiBuilder;

    public function __construct(ApiBuilder $apiBuilder, ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
        $this->apiBuilder = $apiBuilder;
    }

    /**
     * @param MetricInterface[] $metrics
     * @throws ApiException
     */
    public function post(array $metrics): MetricPostResponse {

        $api = $this->apiBuilder->build(MetricV1Api::class, null);

        /** @var MetricNewRelicApi $metricNewRelicApi */
        $metricNewRelicApi = $this->objectManager->create(MetricNewRelicApi::class, ['api' => $api]);

        return $metricNewRelicApi->post($metrics);
    }
}
