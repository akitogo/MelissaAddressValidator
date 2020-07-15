<?php

namespace Akitogo\MelissaAddressValidator\Plugin;

use Akitogo\MelissaAddressValidator\Helper\Data;
use Magento\Checkout\Block\Checkout\LayoutProcessor as MagentoLayoutProcessor;

class LayoutProcessor
{
    protected $helperData;

    public function __construct(Data $helperData)
    {
        $this->helperData = $helperData;
    }

    protected function isEnabled()
    {
        return $this->helperData->getAddressConfig('enable');
    }

    public function afterProcess(MagentoLayoutProcessor $layoutProcessor, $jsLayout = [])
    {
        if ($this->isEnabled()) {
            $jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['additional-payment-validators']['children']['akitogoMelissaAddressValidator'] = [
                'component' => 'Akitogo_MelissaAddressValidator/js/view/registerValidator'
            ];
        } else {
            unset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children']['additional-payment-validators']['children']['akitogoMelissaAddressValidator']);
        }
        return $jsLayout;
    }
}
