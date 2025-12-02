<?php
declare(strict_types=1);

namespace GardenLawn\Company\Plugin\Controller\Address;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressInterfaceFactory;
use Magento\Customer\Controller\Address\FormPost;
use Magento\Customer\Model\Session;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\UrlFactory;
use GardenLawn\Company\Api\Data\CeidgService;
use GardenLawn\Company\Helper\Data as CompanyHelper;
use GardenLawn\Company\Model\RegionFinderTrait;

class FormPostPlugin
{
    use RegionFinderTrait;

    private CompanyHelper $companyHelper;
    private CeidgService $ceidgService;
    private RequestInterface $request;
    private ManagerInterface $messageManager;
    private RedirectFactory $resultRedirectFactory;
    private AddressRepositoryInterface $addressRepository;
    private CustomerRepositoryInterface $customerRepository;
    private Session $customerSession;
    private UrlFactory $urlFactory;
    private AddressInterfaceFactory $addressFactory;
    private RegionFactory $regionFactory;

    public function __construct(
        CompanyHelper $companyHelper,
        CeidgService $ceidgService,
        RequestInterface $request,
        ManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory,
        AddressRepositoryInterface $addressRepository,
        CustomerRepositoryInterface $customerRepository,
        Session $customerSession,
        UrlFactory $urlFactory,
        AddressInterfaceFactory $addressFactory,
        RegionFactory $regionFactory
    ) {
        $this->companyHelper = $companyHelper;
        $this->ceidgService = $ceidgService;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->addressRepository = $addressRepository;
        $this->customerRepository = $customerRepository;
        $this->customerSession = $customerSession;
        $this->urlFactory = $urlFactory;
        $this->addressFactory = $addressFactory;
        $this->regionFactory = $regionFactory;
    }

    /**
     * @throws LocalizedException
     */
    public function aroundExecute(FormPost $subject, callable $proceed)
    {
        if (!in_array($this->companyHelper->getCurrentCustomerGroupId(), $this->companyHelper->getB2bCustomerGroups())) {
            return $proceed();
        }

        $redirect = $this->resultRedirectFactory->create();
        $nip = $this->request->getParam('vat_id');

        if (!$nip) {
            $this->messageManager->addErrorMessage(__('NIP is a required field for B2B customers.'));
            return $redirect->setUrl($this->getRedirectUrl());
        }

        try {
            $ceidgData = $this->ceidgService->getDataByNip($nip);
            if (!$ceidgData) {
                throw new LocalizedException(__('Could not find company data for the provided NIP.'));
            }

            $customer = $this->customerRepository->getById($this->customerSession->getCustomerId());
            $customer->setFirstname($ceidgData->firstName);
            $customer->setLastname($ceidgData->lastName);

            $billingAddressId = $customer->getDefaultBilling();
            $shippingAddressId = $customer->getDefaultShipping();
            $shippingAddressCreated = false;

            // Always create/update the default billing address
            $billingAddress = $this->getOrCreateAddress($billingAddressId);
            $this->updateAddressFromCeidg($billingAddress, $ceidgData);
            $billingAddress->setIsDefaultBilling(true);
            $savedBillingAddress = $this->addressRepository->save($billingAddress);
            $customer->setDefaultBilling($savedBillingAddress->getId());

            // Create a default shipping address ONLY if one doesn't exist
            if (!$shippingAddressId) {
                $shippingAddress = $this->addressFactory->create();
                $shippingAddress->setCustomerId($customer->getId());
                $this->updateAddressFromCeidg($shippingAddress, $ceidgData);
                $shippingAddress->setIsDefaultShipping(true);
                $savedShippingAddress = $this->addressRepository->save($shippingAddress);
                $customer->setDefaultShipping($savedShippingAddress->getId());
                $shippingAddressCreated = true;
            }

            $this->customerRepository->save($customer);

            $message = __('Your billing address has been updated based on CEIDG data.');
            if ($shippingAddressCreated) {
                $message .= ' ' . __('A new default shipping address was also created.');
            }
            $this->messageManager->addSuccessMessage($message);

            return $redirect->setPath('customer/address/index');

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('An error occurred: %1', $e->getMessage()));
            return $redirect->setUrl($this->getRedirectUrl());
        }
    }

    private function getOrCreateAddress(?string $addressId): AddressInterface
    {
        if ($addressId) {
            try {
                return $this->addressRepository->getById($addressId);
            } catch (\Exception $e) {
                // Not found, create new
            }
        }
        $newAddress = $this->addressFactory->create();
        $newAddress->setCustomerId($this->customerSession->getCustomerId());
        return $newAddress;
    }

    private function updateAddressFromCeidg(AddressInterface $address, object $ceidgData): void
    {
        $regionId = $this->getRegionIdByName($ceidgData->region, 'PL');

        $address->setFirstname($ceidgData->firstName)
            ->setLastname($ceidgData->lastName)
            ->setCompany($ceidgData->companyName)
            ->setVatId($this->request->getParam('vat_id'))
            ->setCountryId('PL')
            ->setPostcode($ceidgData->postcode)
            ->setCity($ceidgData->city)
            ->setStreet([$ceidgData->street])
            ->setRegionId($regionId)
            ->setTelephone('000000000'); // Telephone is required
    }

    private function getRedirectUrl(): string
    {
        $url = $this->urlFactory->create()->getUrl('*/*/new', ['_secure' => true]);
        if ($addressId = $this->request->getParam('id')) {
            $url = $this->urlFactory->create()->getUrl('*/*/edit', ['_secure' => true, 'id' => $addressId]);
        }
        return $url;
    }
}
