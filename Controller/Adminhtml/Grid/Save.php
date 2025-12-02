<?php

namespace GardenLawn\Company\Controller\Adminhtml\Grid;

use Exception;
use GardenLawn\Company\Model\GridFactory;
use GardenLawn\Company\Model\CompanyCommentFactory;
use GardenLawn\Company\Model\Status;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class Save extends Action
{
    /**
     * @var GridFactory
     */
    var GridFactory $gridFactory;
    /**
     * @var CompanyCommentFactory
     */
    var CompanyCommentFactory $commentFactory;

    public function __construct(
        Context               $context,
        GridFactory           $gridFactory,
        CompanyCommentFactory $commentFactory
    )
    {
        parent::__construct($context);
        $this->gridFactory = $gridFactory;
        $this->commentFactory = $commentFactory;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function execute(): void
    {
        $data = $this->getRequest()->getPostValue();
        if (!$data) {
            $this->_redirect('gardenlawn_company/grid/addrow');
            return;
        }
        try {
            $rowData = $this->gridFactory->create();
            $rowData->setData($data);
            if (isset($data['id'])) {
                $rowData->setCompanyId($data['id']);
            }
            $current = $this->gridFactory->create()->load($rowData->getCompanyId());
            if (isset($data['customer_id']) && strlen($data['customer_id']) > 0) {
                $rowData->setCustomerId($data['customer_id']);
            } else {
                $rowData->setCustomerId(null);
            }
            $rowData->save();
            $data['id'] = $rowData->getCompanyId();
            if ($current->getCompanyId()) {
                if ($current->getStatus() != $rowData->getStatus()) {
                    $from = Status::getStatusName($current->getStatus());
                    $to = Status::getStatusName($rowData->getStatus());
                    $message = "Status changed from '$from' to '$to'";
                    $comment = $this->commentFactory->create();
                    $comment->setCompanyId($rowData->getCompanyId());
                    $comment->setText($message);
                    $comment->save();
                }
                if ($current->getEmail() != $rowData->getEmail()) {
                    $from = $current->getEmail();
                    $to = $rowData->getEmail();
                    $message = "Email changed from '$from' to '$to'";
                    $comment = $this->commentFactory->create();
                    $comment->setCompanyId($rowData->getCompanyId());
                    $comment->setText($message);
                    $comment->save();
                }
                if ($current->getPhone() != $rowData->getPhone()) {
                    $from = $current->getPhone();
                    $to = $rowData->getPhone();
                    $message = "Phone changed from '$from' to '$to'";
                    $comment = $this->commentFactory->create();
                    $comment->setCompanyId($rowData->getCompanyId());
                    $comment->setText($message);
                    $comment->save();
                }
            }
            if (isset($data['comment']) && $data['comment']) {
                $comment = $this->commentFactory->create();
                $comment->setCompanyId($rowData->getCompanyId());
                $comment->setText($data['comment']);
                $comment->save();
            }
            $this->messageManager->addSuccess(__('Row data has been successfully saved.'));
        } catch (Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        $this->_redirect(isset($data['id']) ? 'gardenlawn_company/grid/addrow/id/' . $data['id'] : 'gardenlawn_company/grid/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('GardenLawn_Company::save');
    }
}
