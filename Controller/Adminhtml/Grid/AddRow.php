<?php

namespace GardenLawn\Company\Controller\Adminhtml\Grid;

use GardenLawn\Company\Model\GridFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;

class AddRow extends Action
{
    /**
     * @var Registry
     */
    private Registry $coreRegistry;

    /**
     * @var GridFactory
     */
    private GridFactory $gridFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry ,
     * @param GridFactory $gridFactory
     */
    public function __construct(
        Context     $context,
        Registry    $coreRegistry,
        GridFactory $gridFactory
    )
    {
        parent::__construct($context);
        $this->coreRegistry = $coreRegistry;
        $this->gridFactory = $gridFactory;
    }

    /**
     * Mapped Grid List page.
     * @return Page|ResultInterface
     * @throws LocalizedException
     */
    public function execute(): Page|ResultInterface
    {
        $rowId = (int)$this->getRequest()->getParam('id');
        $rowData = $this->gridFactory->create();
        /** @var Page $resultPage */
        if ($rowId) {
            $rowData = $rowData->load($rowId);
            $rowTitle = $rowData->getTitle();
            if (!$rowData->getCompanyId()) {
                $this->messageManager->addError(__('row data no longer exist.'));
                $this->_redirect('company/grid/rowdata');
            }
        }

        $this->coreRegistry->register('row_data', $rowData);
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $title = $rowId ? __('Edit Row Data ') . $rowTitle : __('Add Row Data');
        $resultPage->getConfig()->getTitle()->prepend($title);
        return $resultPage;
    }

    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('GardenLawn_Company::add_row');
    }
}
