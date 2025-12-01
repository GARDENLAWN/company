<?php
declare(strict_types=1);

namespace GardenLawn\Company\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    public const string XML_PATH_CEIDG_API_BASE_URL = 'ceidg_api/general/base_url';
    public const string XML_PATH_CEIDG_API_PKD_CODE = 'ceidg_api/general/pkd_code';
    public const string XML_PATH_CEIDG_API_VOIVODESHIP = 'ceidg_api/general/voivodeship';
    public const string XML_PATH_CEIDG_API_COMPANY_STATUS = 'ceidg_api/general/company_status';
    public const string XML_PATH_CEIDG_API_TOKEN = 'ceidg_api/general/api_token';

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Get CEIDG API Base URL
     *
     * @return string
     */
    public function getCeidgApiBaseUrl(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CEIDG_API_BASE_URL, ScopeInterface::SCOPE_STORE) ?? "https://dane.biznes.gov.pl/api/ceidg/v2/firmy";
    }

    /**
     * Get CEIDG API PKD Code
     *
     * @return string
     */
    public function getCeidgApiPkdCode(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CEIDG_API_PKD_CODE, ScopeInterface::SCOPE_STORE) ?? "8130Z";
    }

    /**
     * Get CEIDG API Voivodeship
     *
     * @return string
     */
    public function getCeidgApiVoivodeship(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CEIDG_API_VOIVODESHIP, ScopeInterface::SCOPE_STORE) ?? "opolskie";
    }

    /**
     * Get CEIDG API Company Status
     *
     * @return string
     */
    public function getCeidgApiCompanyStatus(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CEIDG_API_COMPANY_STATUS, ScopeInterface::SCOPE_STORE) ?? "AKTYWNY";
    }

    /**
     * Get CEIDG API Token
     *
     * @return string|null
     */
    public function getCeidgApiToken(): ?string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CEIDG_API_TOKEN);
    }
}
