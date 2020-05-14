<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Controller\Index;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use RunAsRoot\PrometheusExporter\Result\PrometheusResult;
use RunAsRoot\PrometheusExporter\Result\PrometheusResultFactory;

class Index extends Action
{
    private $prometheusResultFactory;

    public function __construct(Context $context, PrometheusResultFactory $prometheusResultFactory)
    {
        parent::__construct($context);

        $this->prometheusResultFactory = $prometheusResultFactory;
    }

    public function execute(): PrometheusResult
    {
        return $this->prometheusResultFactory->create();
    }
}
