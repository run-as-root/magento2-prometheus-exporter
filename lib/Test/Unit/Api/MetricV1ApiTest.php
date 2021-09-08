<?php
declare(strict_types=1);

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see       PROJECT_LICENSE.txt
 */

namespace RunAsRoot\NewRelicApi\Test\Unit\Api;

use RunAsRoot\NewRelicApi\Exception\ApiException;
use RunAsRoot\NewRelicApi\Exception\DeserializationFailedException;
use Doctrine\Common\Annotations\AnnotationReader;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\JsonSerializableNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;
use RunAsRoot\NewRelicApi\Api\Metric\MetricV1Api;
use RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes\CountMetric;
use RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes\GaugeMetric;
use RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes\SummaryMetric;
use RunAsRoot\NewRelicApi\Request\Metric\Data\MetricTypes\Value\SummaryMetricValue;
use RunAsRoot\NewRelicApi\Request\Metric\MetricPostRequest;
use RunAsRoot\NewRelicApi\Response\Metric\MetricPostResponse;

class MetricV1ApiTest extends TestCase
{
    const METRIC_RESPONSE_REQUEST_ID = '4e000000-0000-0000-0000-abcd12345678';

    private MetricV1Api $api;

    /** @var ClientInterface|MockObject */
    private $client;

    /**
     * @return Serializer
     */
    public function getJsonSerializer(): Serializer
    {
        $classMetadataFactory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);
        $encoders = [
            new JsonEncoder(),
        ];
        $normalizers = [
            new JsonSerializableNormalizer(),
            new ObjectNormalizer($classMetadataFactory, $metadataAwareNameConverter, null, new ReflectionExtractor()),
            new ArrayDenormalizer(),
        ];

        $serializer = new Serializer($normalizers, $encoders);
        return $serializer;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = $this->createMock(ClientInterface::class);

        $this->api = new MetricV1Api(null, $this->client);
    }

    public function testGaugeMetricRequestObject(): void
    {
        $serializer = $this->getJsonSerializer();
        $requestObject = $this->getGaugeMetricRequestObject();

        $requestJsonFileOrig = file_get_contents(__DIR__ . '/_files/gauge-metric-request.json');
        $requestJsonFile = preg_replace('/\s+/', '', $requestJsonFileOrig);

        $jsonFromRequestObject = $serializer->serialize(
            [$requestObject],
            'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['headers']]
        );

        $this->assertEquals($requestJsonFile, $jsonFromRequestObject);
    }

    public function testCountMetricRequestObject(): void
    {
        $serializer = $this->getJsonSerializer();
        $requestObject = $this->getCountMetricRequestObject();

        $requestJsonFileOrig = file_get_contents(__DIR__ . '/_files/count-metric-request.json');
        $requestJsonFile = preg_replace('/\s+/', '', $requestJsonFileOrig);

        $jsonFromRequestObject = $serializer->serialize(
            [$requestObject],
            'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['headers']]
        );

        $this->assertEquals($requestJsonFile, $jsonFromRequestObject);
    }

    public function testSummaryMetricRequestObject(): void
    {
        $serializer = $this->getJsonSerializer();
        $requestObject = $this->getSummaryMetricRequestObject();

        $requestJsonFileOrig = file_get_contents(__DIR__ . '/_files/summary-metric-request.json');
        $requestJsonFile = preg_replace('/\s+/', '', $requestJsonFileOrig);

        $jsonFromRequestObject = $serializer->serialize(
            [$requestObject],
            'json',
            [AbstractNormalizer::IGNORED_ATTRIBUTES => ['headers']]
        );

        $this->assertEquals($requestJsonFile, $jsonFromRequestObject);
    }

    /**
     * @throws ApiException
     */
    public function testMetricOnSuccess(): void
    {
        $responseBody = file_get_contents(__DIR__ . '/_files/metric-request.json');
        $gaugeMetricRequest = $this->getGaugeMetricRequestObject();
        $httpResponse = new Response(200, [], $responseBody);

        $this->client->expects($this->once())->method('send')->willReturn($httpResponse);

        /** @var MetricPostResponse $response */
        $response = $this->api->post($gaugeMetricRequest);

        $this->assertEquals(self::METRIC_RESPONSE_REQUEST_ID, $response->getRequestId());
    }

    /**
     * @throws ApiException
     */
    public function testExceptionOnResponseDeserialization(): void
    {
        $responseBody = file_get_contents(__DIR__ . '/_files/gauge-metric-request.json');
        $gaugeMetricRequest = $this->getGaugeMetricRequestObject();
        $httpResponse = new Response(200, [], $responseBody);

        // SERIALIZER
        /** @var SerializerInterface|\PHPUnit_Framework_MockObject_MockObject $serializer */
        $serializer = $this->createMock(SerializerInterface::class);

        $this->client->expects($this->once())->method('send')->willReturn($httpResponse);
        $serializer->expects($this->once())->method('deserialize')->willThrowException(new \Exception());

        $api = new MetricV1Api(null, $this->client, $serializer);

        $this->expectException(DeserializationFailedException::class);
        $this->expectExceptionMessage('Error during deserialization');
        $api->post($gaugeMetricRequest);
    }

    /**
     * @throws ApiException
     */
    public function testExceptionOnDeserializationError(): void
    {
        $brokenResponse = new Response(200, [], '[asdf]');
        $gaugeMetricRequest = $this->getGaugeMetricRequestObject();

        $this->client->expects($this->once())->method('send')->willReturn($brokenResponse);

        $this->expectException(DeserializationFailedException::class);
        $this->expectExceptionMessage('Error during deserialization');

        $this->api->post($gaugeMetricRequest);
    }

    /**
     * @throws ApiException
     */
    public function testExceptionOnRequestError(): void
    {
        $this->expectException(TransferException::class);

        $this->client->expects($this->once())->method('send')->willThrowException(
            new TransferException()
        );

        $gaugeMetricRequest = $this->getGaugeMetricRequestObject();
        $this->api->post($gaugeMetricRequest);
    }

    private function getGaugeMetricRequestObject(): MetricPostRequest
    {
        $request = new MetricPostRequest();

        $metricObject = new GaugeMetric();
        $metricObject->setName('temperature');
        $metricObject->setValue(15);
        $metricObject->setTimestamp(1630557133);
        $metricObject->setAttributes(['city' => 'Poland', 'state' => 'Oregon']);

        $metricObject->addAttribute('city', 'Poland');
        $metricObject->addAttribute('state', 'Oregon');

        $request->addMetric($metricObject);

        return $request;
    }

    private function getCountMetricRequestObject(): MetricPostRequest
    {
        $request = new MetricPostRequest();

        $metricObject = new CountMetric();
        $metricObject->setName('cache.miss');
        $metricObject->setValue(15);
        $metricObject->setIntervalMs(10000);
        $metricObject->setTimestamp(1630557133);

        $metricObject->addAttribute('status', true);
        $metricObject->addAttribute('cache.name', 'mycache');

        $request->addMetric($metricObject);

        return $request;
    }

    private function getSummaryMetricRequestObject(): MetricPostRequest
    {
        $request = new MetricPostRequest();

        $metricValue = new SummaryMetricValue();
        $metricValue->setCount(23);
        $metricValue->setSum(0.0046158);
        $metricValue->setMin(0.0005358);
        $metricValue->setMax(0.0018549);

        $metricObject = new SummaryMetric();
        $metricObject->setName('service.response.duration');
        $metricObject->setIntervalMs(10000);
        $metricObject->setTimestamp(1630557133);
        $metricObject->setValue($metricValue);

        $metricObject->addAttribute('host.name', 'app.name');
        $metricObject->addAttribute('state', 'foo');
        $metricObject->addAttribute('status', true);

        $request->addMetric($metricObject);

        return $request;
    }
}
