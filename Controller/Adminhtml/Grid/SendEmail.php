<?php

namespace GardenLawn\Company\Controller\Adminhtml\Grid;

use Exception;
use GardenLawn\Company\Model\GridFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Mail\MessageInterfaceFactory;
use Magento\Framework\Mail\TransportInterfaceFactory;

class SendEmail extends Action
{
    protected GridFactory $gridFactory;
    protected MessageInterfaceFactory $messageInterfaceFactory;
    protected TransportInterfaceFactory $mailTransportFactory;
    protected CustomerInterfaceFactory $customerFactory;

    public function __construct(
        Context                   $context,
        GridFactory               $gridFactory,
        MessageInterfaceFactory   $messageInterfaceFactory,
        TransportInterfaceFactory $mailTransportFactory,
        CustomerInterfaceFactory  $customerFactory
    )
    {
        parent::__construct($context);
        $this->gridFactory = $gridFactory;
        $this->messageInterfaceFactory = $messageInterfaceFactory;
        $this->mailTransportFactory = $mailTransportFactory;
        $this->customerFactory = $customerFactory;
    }

    public function execute(): void
    {
        try {
            $companyId = (int)$this->getRequest()->getParam('company_id');
            $customerId = (int)$this->getRequest()->getParam('customer_id');

            $message = $this->messageInterfaceFactory->create();

            $message = $message->setFrom('Garden Lawn & Irrigation')
                ->setFromAddress('marcin.piechota@gardenlawn.pl');

            if ($companyId) {
                $rowData = $this->gridFactory->create();
                $company = $rowData->load($companyId);

                $message = $message->addTo($company->getEmail())
                    ->setSubject('')
                    ->setBodyHtml('');
            }

            if ($customerId) {
                $customerFactory = $this->customerFactory->create();
                $customer = $customerFactory->loadById($customerId);

                $message = $message->addTo($customer->getEmail())
                    ->setSubject('')
                    ->setBodyHtml('');
            }

            $transport = $this->mailTransportFactory->create(['message' => $message]);
            $transport->sendMessage();
        } catch (Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('GardenLawn_Company::send_email');
    }
}
