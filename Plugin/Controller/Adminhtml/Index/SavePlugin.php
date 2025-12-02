<?php
declare(strict_types=1);

namespace GardenLawn\Company\Plugin\Controller\Adminhtml\Index;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Controller\Adminhtml\Index\Save;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use GardenLawn\Company\Api\Data\CeidgService;
use GardenLawn\Company\Helper\Data as CompanyHelper;

class SavePlugin
{
    private CompanyHelper $companyHelper;
    private CeidgService $ceidgService;
    private AddressRepositoryInterface $addressRepository;
    private CustomerRepositoryInterface $customerRepository;
    private AddressInterfaceFactory $addressFactory;
    private ManagerInterface $messageManager;
    private RequestInterface $request;

    public function __construct(
        CompanyHelper $companyHelper,
        CeidgService $ceidgService,
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        AddressInterfaceFactory $addressFactory,
        ManagerInterface $messageManager,
        RequestInterface $request
    ) {
        $this->companyHelper = $companyHelper;
        $this->ceidgService = $ceidgService;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->addressFactory = $addressFactory;
        $this->messageManager = $messageManager;
        $this->request = $request;
    }

    public function afterExecute(Save $subject, $result)
    {
        $customerId = (int)$this->request->getParam('customer_id');
        if (!$customerId) {
            // It's a new customer, get ID from result
            // This part is tricky as the ID is not easily available from the result.
            // A more robust way would be to observe the customer_save_after event.
            // For this plugin, we'll focus on existing customers for simplicity.
            return $result;
        }

        $customerData = $this->request->getPost('customer');
        $groupId = (int)$customerData['group_id'];

        if (in_array($groupId, $this->companyHelper->getB2bCustomerGroups())) {
            $taxvat = $customerData['taxvat'] ?? null;

            if (!$taxvat) {
                $this->messageManager->addWarningMessage(__('NIP was not provided. B2B addresses were not updated.'));
                return $result;
            }

            try {
                $ceidgData = $this->ceidgService->getDataByNip($taxvat);
                if (!$ceidgData) {
                    throw new LocalizedException(__('Could not find company data for the provided NIP.'));
                }

                $customer = $this->customerRepository->getById($customerId);
                $billingAddressId = $customer->getDefaultBilling();
                $shippingAddressId = $customer->getDefaultShipping();
                $shippingAddressCreated = false;

                // Always create/update the default billing address
                $billingAddress = $this->getOrCreateAddress($customerId, $billingAddressId);
                $this->updateAddressFromCeidg($billingAddress, $ceidgData, $customerData);
                $billingAddress->setIsDefaultBilling(true);
                $savedBillingAddress = $this->addressRepository->save($billingAddress);
                $customer->setDefaultBilling($savedBillingAddress->getId());

                // Create a default shipping address ONLY if one doesn't exist
                if (!$shippingAddressId) {
                    $shippingAddress = $this->addressFactory->create();
                    $shippingAddress->setCustomerId($customerId);
                    $this->updateAddressFromCeidg($shippingAddress, $ceidgData, $customerData);
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

            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred while updating B2B addresses: %1', $e->getMessage()));
            }
        }

        return $result;
    }

    private function getOrCreateAddress(int $customerId, ?string $addressId): AddressInterface
    {
        if ($addressId) {
            try {
                return $this->addressRepository->getById($addressId);
            } catch (\Exception $e) {
                // Not found, create new
            }
        }
        $newAddress = $this->addressFactory->create();
        $newAddress->setCustomerId($customerId);
        return $newAddress;
    }

    private function updateAddressFromCeidg(AddressInterface $address, object $ceidgData, array $customerData): void
    {
        $streetParts = explode(' ', $ceidgData->street, 2);

        $address->setFirstname($customerData['firstname'])
            ->setLastname($customerData['lastname'])
            ->setCompany($ceidgData->name)
            ->setVatId($customerData['taxvat'])
            ->setCountryId('PL')
            ->setPostcode($ceidgData->postcode)
            ->setCity($ceidgData->city)
            ->setStreet([$streetParts[0], $streetParts[1] ?? ''])
            ->setTelephone('123456789'); // Telephone is required
    }
}
