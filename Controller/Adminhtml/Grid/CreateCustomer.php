<?php

namespace GardenLawn\Company\Controller\Adminhtml\Grid;

use Exception;
use GardenLawn\Company\Enum\Status;
use GardenLawn\Company\Model\CompanyCommentFactory;
use GardenLawn\Company\Model\GridFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Customer\Model\AccountManagement;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;

class CreateCustomer extends Action
{
    protected GridFactory $gridFactory;
    protected StoreManagerInterface $storeManager;
    protected CustomerInterfaceFactory $customerFactory;
    protected CustomerRepositoryInterface $customerRepository;
    protected ObjectManager $objectManager;
    protected AdapterInterface $connection;
    protected CompanyCommentFactory $commentFactory;
    protected AccountManagement $accountManagement;
    protected Random $mathRandom;

    public function __construct(
        Context                         $context,
        GridFactory                     $gridFactory,
        StoreManagerInterface           $storeManager,
        CustomerInterfaceFactory        $customerFactory,
        CustomerRepositoryInterface     $customerRepository,
        CompanyCommentFactory           $commentFactory,
        AccountManagement               $accountManagement,
        Random                          $mathRandom
    )
    {
        parent::__construct($context);
        $this->gridFactory = $gridFactory;
        $this->storeManager = $storeManager;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->commentFactory = $commentFactory;
        $this->mathRandom = $mathRandom;
        $this->accountManagement = $accountManagement;
        $this->objectManager = ObjectManager::getInstance();
        $resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
        $this->connection = $resource->getConnection();
    }

    public function execute(): void
    {
        $rowId = (int)$this->getRequest()->getParam('id');
        $rowData = $this->gridFactory->create();
        /** @var Page $resultPage */
        if ($rowId) {
            $rowData = $rowData->load($rowId);
            if (!$rowData->getCompanyId()) {
                $this->messageManager->addError(__('row data no longer exist.'));
                $this->_redirect('gardenlawn_company/grid/rowdata');
            }

            try {
                $this->connection->beginTransaction();

                $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
                $customer = $this->customerFactory->create();
                $customer->setWebsiteId($websiteId);
                $customer->setEmail($rowData['email']);
                $customer->setTaxvat($rowData['nip']);
                $customer->setFirstname("First Name");
                $customer->setLastname("Last name");
                $customer->setGroupId($rowData['customer_group_id']);

                $newCustomer = $this->customerRepository->save($customer);

                $current = $this->gridFactory->create()->load($rowData->getCompanyId());

                $rowData->setCustomerId($newCustomer->getId());
                $rowData->setStatus(Status::CustomerCreate->value);
                $rowData->save();

                $customer = $this->customerRepository->getById($newCustomer->getId());
                $newLinkToken = $this->mathRandom->getUniqueHash();
                $this->accountManagement->changeResetPasswordLinkToken($customer, $newLinkToken);

                if ($current->getStatus() != $rowData->getStatus()) {
                    $from = \GardenLawn\Company\Model\Status::getStatusName($current->getStatus());
                    $to = \GardenLawn\Company\Model\Status::getStatusName($rowData->getStatus());
                    $message = "Status changed from '$from' to '$to'";
                    $comment = $this->commentFactory->create();
                    $comment->setCompanyId($rowData->getCompanyId());
                    $comment->setText($message);
                    $comment->save();
                }

                $data['id'] = $rowData->getCompanyId();
                $this->messageManager->addSuccess(__('Customer has been successfully created.'));

                $this->connection->commit();
            } catch (Exception $e) {
                $this->messageManager->addError(__($e->getMessage()));
                $this->connection->rollBack();
            }
        }

        $this->_redirect(isset($data['id']) ? 'gardenlawn_company/grid/addrow/id/' . $data['id'] : 'gardenlawn_company/grid/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('GardenLawn_Company::create_customer');
    }
}
