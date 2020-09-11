<?php

declare(strict_types=1);

namespace RunAsRoot\PrometheusExporter\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class DisabledText extends Field
{
    public function render(AbstractElement $element)
    {
        //$element->setDataUsingMethod('disabled', true);
        return parent::render($element);
    }
}
