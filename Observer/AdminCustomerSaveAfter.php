<?php
declare(strict_types=1);

namespace GardenLawn\Company\Observer;

use Exception;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use GardenLawn\Company\Api\Data\CeidgService;
use GardenLawn\Company\Helper\Data as CompanyHelper;

class AdminCustomerSaveAfter implements ObserverInterface
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
        $customerDataObject = $observer->getEvent()->getCustomerDataObject();
        $groupId = (int)$customerDataObject->getGroupId();

        if (in_array($groupId, $this->companyHelper->getB2bCustomerGroups())) {
            $taxvat = $customerDataObject->getTaxvat();

            if (!$taxvat) {
                $this->messageManager->addWarningMessage(__('NIP was not provided. B2B addresses were not updated.'));
                return;
            }

            try {
                $ceidgData = $this->ceidgService->getDataByNip($taxvat);
                if (!$ceidgData) {
                    throw new LocalizedException(__('Could not find company data for the provided NIP.'));
                }

                $customer = $this->customerRepository->getById($customerDataObject->getId());
                $billingAddressId = $customer->getDefaultBilling();
                $shippingAddressId = $customer->getDefaultShipping();
                $shippingAddressCreated = false;

                // Always create/update the default billing address
                $billingAddress = $this->getOrCreateAddress((int)$customer->getId(), $billingAddressId);
                $this->updateAddressFromCeidg($billingAddress, $ceidgData, $customerDataObject);
                $billingAddress->setIsDefaultBilling(true);
                $savedBillingAddress = $this->addressRepository->save($billingAddress);
                $customer->setDefaultBilling($savedBillingAddress->getId());

                // Create a default shipping address ONLY if one doesn't exist
                if (!$shippingAddressId) {
                    $shippingAddress = $this->addressFactory->create();
                    $shippingAddress->setCustomerId($customer->getId());
                    $this->updateAddressFromCeidg($shippingAddress, $ceidgData, $customerDataObject);
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

            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while updating B2B addresses: %1', $e->getMessage()));
            }
        }
    }

    private function getOrCreateAddress(int $customerId, ?string $addressId): AddressInterface
    {
        if ($addressId) {
            try {
                return $this->addressRepository->getById((int)$addressId);
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
