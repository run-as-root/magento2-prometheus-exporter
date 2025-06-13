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
        if ($this->config->getTokenValidationEnabled()) {
            $token = sprintf('Bearer %s', $this->config->getToken());
            $authorizationHeader = $this->getRequest()->getHeader('Authorization');

            if ($token !== $authorizationHeader) {
                /** @var \Magento\Framework\Controller\Result\Raw $result */
                $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
                $result->setHttpResponseCode(Http::STATUS_CODE_401);
                $result->setContents('You are not allowed to see these metrics.');

                return $result;
            }
        }

        return $this->prometheusResultFactory->create();
    }
}
