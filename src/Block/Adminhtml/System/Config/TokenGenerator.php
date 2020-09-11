<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class TokenGenerator extends Field
{
    private const SYSTEM_CONFIG_PATH = 'metric_configuration_security_token';

    public static function getFieldId(): string
    {
        return self::SYSTEM_CONFIG_PATH;
    }

    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        if (!$this->getTemplate()) {
            $this->setTemplate('RunAsRoot_PrometheusExporter::system/config/tokenGeneratorButton.phtml');
        }

        return $this;
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $buttonLabel = $originalData['button_label'];
        $this->addData(
            [
                'button_label' => __($buttonLabel),
                'html_id' => $element->getHtmlId(),
                'ajax_url' => $this->_urlBuilder->getUrl('run_as_root_prometheus/prometheus/generatetoken'),
            ]
        );

        return $this->_toHtml();
    }
}
