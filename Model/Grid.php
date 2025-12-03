<?php

namespace GardenLawn\Company\Model;

use DateTime;
use GardenLawn\Company\Api\Data\GridInterface;
use Magento\Framework\Model\AbstractModel;

class Grid extends AbstractModel implements GridInterface
{
    /**
     * CMS page cache tag.
     */
    const string CACHE_TAG = 'gardenlawn_company';

    /**
     * @var string
     */
    protected $_cacheTag = 'gardenlawn_company';

    /**
     * Prefix of model events names.
     *
     * @var string
     */
    protected $_eventPrefix = 'gardenlawn_company';

    /**
     * Initialize resource model.
     */
    protected function _construct(): void
    {
        $this->_init('GardenLawn\Company\Model\ResourceModel\Grid');
    }

    public function getCompanyId(): ?int
    {
        return $this->getData(self::company_id);
    }

    public function setCompanyId($companyId): Grid
    {
        return $this->setData(self::company_id, $companyId);
    }

    public function getNip()
    {
        return $this->getData(self::nip);
    }

    public function setNip($nip): Grid
    {
        return $this->setData(self::nip, $nip);
    }

    public function getName(): ?string
    {
        return $this->getData(self::name);
    }

    public function setName($name): Grid
    {
        return $this->setData(self::name, $name);
    }

    public function getUrl(): ?string
    {
        return $this->getData(self::url);
    }

    public function setUrl($url): Grid
    {
        return $this->setData(self::url, $url);
    }

    public function getStatus(): int
    {
        return $this->getData(self::status);
    }

    public function setStatus($status): Grid
    {
        return $this->setData(self::status, $status);
    }

    public function getCustomerId(): ?int
    {
        return $this->getData(self::customer_id);
    }

    public function setCustomerId($customerId): Grid
    {
        return $this->setData(self::customer_id, $customerId);
    }

    public function getPhone(): ?string
    {
        return $this->getData(self::phone);
    }

    public function setPhone($phone): Grid
    {
        return $this->setData(self::phone, $phone);
    }

    public function getEmail(): ?string
    {
        return $this->getData(self::email);
    }

    public function setEmail($email): Grid
    {
        return $this->setData(self::email, $email);
    }

    public function getWww(): ?string
    {
        return $this->getData(self::www);
    }

    public function setWww($www): Grid
    {
        return $this->setData(self::www, $www);
    }

    public function getAddress(): ?string
    {
        return $this->getData(self::address);
    }

    public function setAddress($address): Grid
    {
        return $this->setData(self::address, $address);
    }

    public function getDistance(): ?float
    {
        return $this->getData(self::distance);
    }

    public function setDistance($distance): Grid
    {
        return $this->setData(self::distance, $distance);
    }

    public function getStatusName(): string
    {
        return Status::getStatusName($this->getStatus());
    }

    public function getCustomerGroupId(): ?int
    {
        return $this->getData(self::customer_group_id);
    }

    public function setCustomerGroupId($customerGroupId): Grid
    {
        return $this->setData(self::customer_group_id, $customerGroupId);
    }
}
