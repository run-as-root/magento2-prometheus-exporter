<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Controller\Index;

use Laminas\Http\Response;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Response\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use RunAsRoot\PrometheusExporter\Data\Config;
use RunAsRoot\PrometheusExporter\Result\PrometheusResultFactory;

class Index extends Action
{
    private PrometheusResultFactory $prometheusResultFactory;
    private Config $config;

    public function __construct(
        Context $context,
        PrometheusResultFactory $prometheusResultFactory,
        Config $config
    ) {
        parent::__construct($context);

        $this->prometheusResultFactory = $prometheusResultFactory;
        $this->config = $config;
    }

    public function execute(): ResultInterface
    {
        if ($this->config->getTokenValidationEnabled() && !$this->isAuthorized()) {
            /** @var \Magento\Framework\Controller\Result\Raw $result */
            $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
            $result->setHttpResponseCode(Http::STATUS_CODE_401);
            $result->setContents('You are not allowed to see these metrics.');

            return $result;
        }

        return $this->prometheusResultFactory->create();
    }

    private function isAuthorized(): bool
    {
        $token = $this->config->getToken();

        // Fail closed: token authorization is on by default, so until a token has been generated
        // nobody may read the metrics - an empty token must never match a "Bearer " header.
        if (trim($token) === '') {
            return false;
        }

        $authorizationHeader = $this->getRequest()->getHeader('Authorization');

        return is_string($authorizationHeader)
            && hash_equals(sprintf('Bearer %s', $token), $authorizationHeader);
    }
}
