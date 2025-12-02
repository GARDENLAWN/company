<?php
declare(strict_types=1);

namespace GardenLawn\Company\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\State\InputMismatchException;

class AdminForceConfirm implements ObserverInterface
{
    private CustomerRepositoryInterface $customerRepository;
    private RequestInterface $request;

    public function __construct(
        CustomerRepositoryInterface $customerRepository,
        RequestInterface $request
    ) {
        $this->customerRepository = $customerRepository;
        $this->request = $request;
    }

    /**
     * @throws InputMismatchException
     * @throws InputException
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $customerData = $this->request->getPost('customer');
        $forceConfirm = $customerData['force_confirm'] ?? false;

        if ($forceConfirm) {
            $customer = $observer->getEvent()->getCustomer();
            if ($customer->getId() && $customer->getConfirmation()) {
                $customer->setConfirmation(null);
                $this->customerRepository->save($customer);
            }
        }
    }
}
