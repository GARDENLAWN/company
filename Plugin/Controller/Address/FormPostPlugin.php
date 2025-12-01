<?php
declare(strict_types=1);

namespace GardenLawn\Company\Plugin\Controller\Address;

use Magento\Customer\Controller\Address\FormPost;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use GardenLawn\Company\Helper\Data as CompanyHelper;
use GardenLawn\Company\Api\Data\CeidgService;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session;

class FormPostPlugin
{
    private CompanyHelper $companyHelper;
    private CeidgService $ceidgService;
    private RequestInterface $request;
    private ManagerInterface $messageManager;
    private RedirectFactory $resultRedirectFactory;
    private AddressRepositoryInterface $addressRepository;
    private Session $customerSession;

    public function __construct(
        CompanyHelper $companyHelper,
        CeidgService $ceidgService,
        RequestInterface $request,
        ManagerInterface $messageManager,
        RedirectFactory $resultRedirectFactory,
        AddressRepositoryInterface $addressRepository,
        Session $customerSession
    ) {
        $this->companyHelper = $companyHelper;
        $this->ceidgService = $ceidgService;
        $this->request = $request;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->addressRepository = $addressRepository;
        $this->customerSession = $customerSession;
    }

    public function aroundExecute(FormPost $subject, callable $proceed)
    {
        $isB2bCustomer = in_array($this->companyHelper->getCurrentCustomerGroupId(), $this->companyHelper->getB2bCustomerGroups());
        $isDefaultBilling = $this->request->getParam('default_billing');

        if ($isB2bCustomer && $isDefaultBilling) {
            $nip = $this->request->getParam('vat_id');
            if ($nip) {
                try {
                    $ceidgData = $this->ceidgService->getDataByNip($nip);
                    $formData = $this->request->getParams();

                    $validationError = false;
                    if (!$ceidgData) {
                        $validationError = true;
                    } else {
                        $ceidgStreet = $ceidgData->street;
                        $formStreet = implode(' ', $formData['street']);

                        if (
                            $ceidgData->name !== $formData['company'] ||
                            $ceidgStreet !== $formStreet ||
                            $ceidgData->postcode !== $formData['postcode'] ||
                            $ceidgData->city !== $formData['city']
                        ) {
                            $validationError = true;
                        }
                    }

                    if ($validationError) {
                        $this->messageManager->addErrorMessage(__('The provided address data is not compliant with CEIDG.'));
                        $resultRedirect = $this->resultRedirectFactory->create();
                        $url = $this->_buildUrl('*/*/edit', ['_secure' => true, 'id' => $this->request->getParam('id')]);
                        return $resultRedirect->setUrl($url);
                    }
                } catch (\Exception $e) {
                    $this->messageManager->addErrorMessage(__('An error occurred while validating CEIDG data.'));
                    $resultRedirect = $this->resultRedirectFactory->create();
                    $url = $this->_buildUrl('*/*/edit', ['_secure' => true, 'id' => $this->request->getParam('id')]);
                    return $resultRedirect->setUrl($url);
                }
            }
        }

        return $proceed();
    }

    private function _buildUrl($route, $params)
    {
        return $this->resultRedirectFactory->create()->getUrl($route, $params);
    }
}
