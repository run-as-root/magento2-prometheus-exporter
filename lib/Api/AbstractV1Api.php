<?php
declare(strict_types=1);
/**
 * Copyright © Visionet Systems, Inc. All rights reserved.
 */

namespace RunAsRoot\NewRelicApi\Api;

use Doctrine\Common\Annotations\AnnotationReader;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
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
use RunAsRoot\NewRelicApi\Config\ClientConfig;
use RunAsRoot\NewRelicApi\Config\ClientConfigInterface;
use RunAsRoot\NewRelicApi\Exception\DeserializationFailedException;
use RunAsRoot\NewRelicApi\Http\RequestFactory;
use RunAsRoot\NewRelicApi\Response\ResponseInterface;

abstract class AbstractV1Api
{
    protected array $ignoredAttributes = ['headers'];

    protected ClientInterface $client;
    protected SerializerInterface $serializer;
    protected RequestFactory $requestFactory;
    protected ?LoggerInterface $logger;

    public function __construct(
        ?ClientConfigInterface $clientConfig = null,
        ?ClientInterface $client = null,
        ?SerializerInterface $serializer = null,
        ?RequestFactory $requestFactory = null,
        ?LoggerInterface $logger = null
    ) {
        $this->clientConfig = $clientConfig ?: new ClientConfig();
        $this->client = $client ?: new Client();
        $this->serializer = $serializer ?: $this->constructSerializer();
        $this->requestFactory = $requestFactory ?: new RequestFactory();
        $this->logger = $logger;
    }

    /**
     * @throws GuzzleException
     */
    protected function sendRequestAndHandleResponse(
        RequestInterface $httpRequest,
        array $httpOptions,
        string $responseClass,
        $context = []
    ): ResponseInterface {
        $startTimeStr = date('Y-m-d H:i:s');
        if ($this->isLoggable()) {
            $this->logger->info('Request: ' . $httpRequest->getBody());
            $this->logger->info('Start Time: ' . $startTimeStr);
        }
        // Send Request
        $httpResponse = $this->client->send($httpRequest, $httpOptions);
        // Handle Response: Deserzialize Response JSON to PHP Object
        $response = null;
        try {
            $responseContent = $httpResponse->getBody()->getContents();
            $endTimeStr = date('Y-m-d H:i:s');
            $differenceInSeconds = floor((strtotime($endTimeStr) - strtotime($startTimeStr)));
            if ($this->isLoggable()) {
                $this->logger->info('End Time: ' . $endTimeStr);
                $this->logger->info('Time Difference: ' . $differenceInSeconds);
                $this->logger->info('Response: ' . $responseContent);
            }

            if (!empty($responseContent)) {
                /** @var ResponseInterface $response */
                $response = $this->serializer->deserialize($responseContent, $responseClass, 'json', $context);
            }
        } catch (\Exception $e) {
            $msg = 'Error during deserialization';
            if ($this->isLoggable()) {
                $this->logger->error('Error during deserialization: ' . $e->getMessage());
            }
            throw new DeserializationFailedException($httpRequest, $httpResponse, $msg, 0, $e);
        }

        if (!$response instanceof $responseClass) {
            $response = new $responseClass();
        }

        $response->setHttpRequest($httpRequest);
        $response->setHttpResponse($httpResponse);

        return $response;
    }

    protected function createHttpPostRequest(string $path, array $headers = [], ?string $httpBody = null): Request
    {
        $uri = rtrim($this->clientConfig->getServiceUrl(), '/') . $path;
        if ($this->logger !== null) {
            $this->logger->info('Path: ' . $uri);
        }
        $httpHeaders = [
            'Content-Type' => 'application/json',
            'Api-Key' => $this->clientConfig->getApiKey(),
        ];

        if (!empty($headers)) {
            $httpHeaders = array_merge($httpHeaders, $headers);
        }

        return $this->requestFactory->createPostRequest($uri, $httpHeaders, $httpBody);
    }

    /**
     * @throws RuntimeException on file opening failure
     */
    protected function createHttpClientOptions(): array
    {
        $options = [];

        $options['headers'] = [];

        return $options;
    }

    protected function constructSerializer(): SerializerInterface
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

        return new Serializer($normalizers, $encoders);
    }

    protected function getSerializerContext(): array
    {
        return [
            AbstractNormalizer::IGNORED_ATTRIBUTES => $this->ignoredAttributes,
            ObjectNormalizer::SKIP_NULL_VALUES => true,
            ObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true,
        ];
    }

    protected function isLoggable(): bool
    {
        return $this->clientConfig->isDebugAllowed() && $this->logger !== null;
    }

    protected function log(string $message): void
    {
        if (!$this->isLoggable()) {
            return;
        }

        $this->logger->info($message);
    }
}
