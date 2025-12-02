<?php
declare(strict_types=1);

namespace GardenLawn\Company\Observer;

use Exception;
use GardenLawn\Company\Api\Data\Exception\CeidgApiException;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use GardenLawn\Company\Api\Data\CeidgService;
use GardenLawn\Company\Helper\Data as CompanyHelper;

class AdminCustomerSaveCommitAfter implements ObserverInterface
{
    private CompanyHelper $companyHelper;
    private CeidgService $ceidgService;
    private AddressRepositoryInterface $addressRepository;
    private CustomerRepositoryInterface $customerRepository;
    private AddressInterfaceFactory $addressFactory;
    private ManagerInterface $messageManager;

    public function __construct(
        CompanyHelper $companyHelper,
        CeidgService $ceidgService,
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        AddressInterfaceFactory $addressFactory,
        ManagerInterface $messageManager
    ) {
        $this->companyHelper = $companyHelper;
        $this->ceidgService = $ceidgService;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->addressFactory = $addressFactory;
        $this->messageManager = $messageManager;
    }

    public function execute(Observer $observer): void
    {
        $customerModel = $observer->getEvent()->getCustomer();
        $customerId = $customerModel->getId();
        if (!$customerId) {
            return;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $groupId = (int)$customer->getGroupId();
            if (in_array($groupId, $this->companyHelper->getB2bCustomerGroups())) {
                $this->handleB2bAddressUpdate($customer);
            }
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred in a post-save operation: %1', $e->getMessage()));
        }
    }

    /**
     * @throws CeidgApiException
     * @throws LocalizedException
     */
    private function handleB2bAddressUpdate(CustomerInterface $customer): void
    {
        $taxvat = $customer->getTaxvat();
        if (!$taxvat) {
            $this->messageManager->addWarningMessage(__('NIP was not provided. B2B addresses were not updated.'));
            return;
        }

        $ceidgData = $this->ceidgService->getDataByNip($taxvat);
        if (!$ceidgData) {
            throw new LocalizedException(__('Could not find company data for the provided NIP.'));
        }

        $billingAddressId = $customer->getDefaultBilling();
        $shippingAddressId = $customer->getDefaultShipping();
        $shippingAddressCreated = false;

        // Always create/update the default billing address
        $billingAddress = $this->getOrCreateAddress((int)$customer->getId(), $billingAddressId);
        $this->updateAddressFromCeidg($billingAddress, $ceidgData, $customer);
        $billingAddress->setIsDefaultBilling(true);
        $savedBillingAddress = $this->addressRepository->save($billingAddress);
        $customer->setDefaultBilling($savedBillingAddress->getId());

        // Create a default shipping address ONLY if one doesn't exist
        if (!$shippingAddressId) {
            $shippingAddress = $this->addressFactory->create();
            $shippingAddress->setCustomerId($customer->getId());
            $this->updateAddressFromCeidg($shippingAddress, $ceidgData, $customer);
            $shippingAddress->setIsDefaultShipping(true);
            $savedShippingAddress = $this->addressRepository->save($shippingAddress);
            $customer->setDefaultShipping($savedShippingAddress->getId());
            $shippingAddressCreated = true;
        }

        $this->customerRepository->save($customer);

        $message = __('Customer\'s billing address has been updated based on CEIDG data.');
        if ($shippingAddressCreated) {
            $message .= ' ' . __('A new default shipping address was also created.');
        }
        $this->messageManager->addSuccessMessage($message);
    }

    private function getOrCreateAddress(int $customerId, ?string $addressId): AddressInterface
    {
        if ($addressId) {
            try {
                return $this->addressRepository->getById($addressId);
            } catch (Exception) {
                // Not found, create new
            }
        }
        $newAddress = $this->addressFactory->create();
        $newAddress->setCustomerId($customerId);
        return $newAddress;
    }

    private function updateAddressFromCeidg(AddressInterface $address, object $ceidgData, CustomerInterface $customerData): void
    {
        $address->setFirstname($customerData->getFirstname())
            ->setLastname($customerData->getLastname())
            ->setCompany($ceidgData->name)
            ->setVatId($customerData->getTaxvat())
            ->setCountryId('PL')
            ->setPostcode($ceidgData->postcode)
            ->setCity($ceidgData->city)
            ->setStreet([$ceidgData->street])
            ->setTelephone('000000000'); // Telephone is required
    }
}
