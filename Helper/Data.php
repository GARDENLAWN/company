<?php
declare(strict_types=1);

namespace GardenLawn\Company\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper implements ArgumentInterface
{
    public const string XML_PATH_CEIDG_API_BASE_URL = 'ceidg_api/general/base_url';
    public const string XML_PATH_CEIDG_API_PKD_CODE = 'ceidg_api/general/pkd_code';
    public const string XML_PATH_CEIDG_API_VOIVODESHIP = 'ceidg_api/general/voivodeship';
    public const string XML_PATH_CEIDG_API_COMPANY_STATUS = 'ceidg_api/general/company_status';
    public const string XML_PATH_CEIDG_API_TOKEN = 'ceidg_api/general/api_token';
    public const string XML_PATH_B2B_CUSTOMER_GROUPS = 'gardenlawn_core/b2b/customer_groups';

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor,
        Session $customerSession
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->customerSession = $customerSession;
    }

    /**
     * Get CEIDG API Base URL
     *
     * @return string
     */
    public function getCeidgApiBaseUrl(): string
    {
        return $this->scopeConfig->getValue(self::XML_PATH_CEIDG_API_BASE_URL, ScopeInterface::SCOPE_STORE) ?? "https://dane.biznes.gov.pl/api/ceidg/v3/";
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
        $token = $this->scopeConfig->getValue(self::XML_PATH_CEIDG_API_TOKEN);
        return $token ? $this->encryptor->decrypt($token) : null;
    }

    /**
     * Get B2B Customer Groups
     *
     * @return array
     */
    public function getB2bCustomerGroups(): array
    {
        $groups = $this->scopeConfig->getValue(self::XML_PATH_B2B_CUSTOMER_GROUPS, ScopeInterface::SCOPE_STORE);
        return $groups ? explode(',', $groups) : [];
    }

    /**
     * Get Current Customer GroupId
     *
     * @return int
     */
    public function getCurrentCustomerGroupId(): int
    {
        try {
            return (int)$this->customerSession->getCustomerGroupId();
        } catch (LocalizedException $e) {
            $this->_logger->error($e->getMessage());
            return 0;
        }
    }
}
