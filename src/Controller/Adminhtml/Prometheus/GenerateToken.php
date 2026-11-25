<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Controller\Adminhtml\Prometheus;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Oauth\Helper\Oauth;

/**
 * Extends the backend action on purpose: admin authentication and the ACL check are only applied to
 * descendants of \Magento\Backend\App\AbstractAction, not to every controller that sits in the adminhtml area.
 */
class GenerateToken extends Action
{
    /**
     * Same resource as the configuration section that renders the "Generate" button.
     */
    public const ADMIN_RESOURCE = 'RunAsRoot_PrometheusExporter::config_runasroot_prometheusexporter';

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
