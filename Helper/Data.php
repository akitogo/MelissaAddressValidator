<?php

namespace Akitogo\MelissaAddressValidator\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const XML_PATH_MELISSA = 'melissa/';

    public function getConfigValue($field, $scope = ScopeInterface::SCOPE_STORE, $storeCode = null)
    {
        return $this->scopeConfig->getValue($field, $scope, $storeCode);
    }

    public function getAddressConfig($code, $scope = ScopeInterface::SCOPE_STORE, $storeCode = null)
    {
        return $this->getConfigValue(self::XML_PATH_MELISSA .'address/'. $code, $scope, $storeCode);
    }
}
