<?php
declare(strict_types=1);

namespace GardenLawn\Company\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;

class Confirm extends Action
{
    private CustomerRepositoryInterface $customerRepository;
    private JsonFactory $resultJsonFactory;

    public function __construct(
        Context $context,
        CustomerRepositoryInterface $customerRepository,
        JsonFactory $resultJsonFactory
    ) {
        parent::__construct($context);
        $this->customerRepository = $customerRepository;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $customerId = $this->getRequest()->getParam('id');

        if (!$customerId) {
            return $result->setData(['success' => false, 'message' => __('Customer ID is missing.')]);
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            if ($customer->getConfirmation()) {
                $customer->setConfirmation(null);
                $this->customerRepository->save($customer);
                $this->messageManager->addSuccessMessage(__('The customer account has been confirmed.'));
                return $result->setData(['success' => true]);
            } else {
                return $result->setData(['success' => false, 'message' => __('The customer account is already confirmed.')]);
            }
        } catch (LocalizedException $e) {
            return $result->setData(['success' => false, 'message' => $e->getMessage()]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => __('An error occurred while confirming the account.')]);
        }
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Customer::customer');
    }
}
