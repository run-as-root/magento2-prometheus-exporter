<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Controller\Adminhtml\Prometheus;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Oauth\Helper\Oauth;

class GenerateToken extends Action
{
    private $oauthHelper;

    public function __construct(Context $context, Oauth $oauthHelper)
    {
        parent::__construct($context);
        $this->oauthHelper = $oauthHelper;
    }

    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData([ 'token' => $this->oauthHelper->generateTokenSecret() ]);

        return $result;
    }
}
