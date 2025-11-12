<?php

namespace GardenLawn\Company\Api\Data;

interface GridInterface
{
    /**
     * Constants for keys of data array. Identical to the name of the getter in snake case.
     */
    const  string company_id = 'company_id';
    const  string nip = 'nip';
    const  string name = 'name';
    const  string phone = 'phone';
    const  string email = 'email';
    const  string www = 'www';
    const  string url = 'url';
    const  string address = 'address';
    const  string distance = 'distance';
    const  string status = 'status';
    const  string statusname = 'statusname';
    const  string customer_id = 'customer_id';
    const  string customer_group_id = 'customer_group_id';

    public function getCompanyId(): ?int;
    public function setCompanyId($companyId);
    public function getNip();
    public function setNip($nip);
    public function getName(): ?string;
    public function setName($name);
    public function getPhone(): ?string;
    public function setPhone($phone);
    public function getEmail(): ?string;
    public function setEmail($email);
    public function getWww(): ?string;
    public function setWww($www);
    public function getUrl(): ?string;
    public function setUrl($url);
    public function getAddress(): ?string;
    public function setAddress($address);
    public function getDistance(): ?float;
    public function setDistance($distance);
    public function getStatus(): int;
    public function getStatusName(): string;
    public function setStatus($status);
    public function getCustomerId(): ?int;
    public function setCustomerId($customerId);
    public function getCustomerGroupId(): ?int;
    public function setCustomerGroupId($customerGroupId);
}
