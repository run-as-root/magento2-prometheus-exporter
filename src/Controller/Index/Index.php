<?php

declare(strict_types=1);

/**
 * @copyright see PROJECT_LICENSE.txt
 *
 * @see PROJECT_LICENSE.txt
 */

namespace RunAsRoot\PrometheusExporter\Controller\Index;

use Magento\Framework\App\Action\Context;
use RunAsRoot\PrometheusExporter\Result\PrometheusResultFactory;

class Index extends \Magento\Framework\App\Action\Action
{
    private $prometheusResultFactory;

    public function __construct(
        Context $context,
        PrometheusResultFactory $prometheusResultFactory
    ) {
        parent::__construct($context);

        $this->prometheusResultFactory = $prometheusResultFactory;
    }

    public function execute()
    {
        return $this->prometheusResultFactory->create();
    }
}
