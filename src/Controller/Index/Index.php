<?php

namespace RunAsRoot\PrometheusExporter\Controller\Index;

use \Magento\Framework\Controller\Result\RawFactory;
use \Magento\Framework\App\Action\Context;

class Index extends \Magento\Framework\App\Action\Action
{
    private $rawFactory;

    public function __construct(
        Context $context,
        RawFactory $rawFactory
    ) {
        parent::__construct($context);

        $this->rawFactory = $rawFactory;
    }

    public function execute()
    {
        return $this->rawFactory->create()->setContents('asdasd');
    }
}