<?php

namespace GardenLawn\Company\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    const string XML_PATH_XOAUTH2_BEARER = 'gardenlawn_company/ceidg_service/xoauth2_bearer';

    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function getXoauth2Bearer($store = null)
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_XOAUTH2_BEARER,
            ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
