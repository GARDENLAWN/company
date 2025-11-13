<?php

namespace GardenLawn\Company\Block\Adminhtml;

use GardenLawn\Company\Model\ResourceModel\CompanyComment\CollectionFactory;
use GardenLawn\Company\Model\Status;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class CompanyComment extends Template
{
    private Registry $coreRegistry;
    private CollectionFactory $commentFactory;

    public function __construct(
        Context           $context,
        CollectionFactory $commentFactory,
        Registry          $coreRegistry)
    {
        parent::__construct($context);
        $this->commentFactory = $commentFactory;
        $this->coreRegistry = $coreRegistry;
    }

    public function getComments(): array
    {
        $companyId = $this->coreRegistry->registry("row_data")?->getCompanyId() ?? 0;
        $comment = $this->commentFactory->create();
        $comment->addFilter("company_id", $companyId);
        $comment->setOrder("created_at", $comment::SORT_ORDER_DESC);

        $statuses = Status::getOptionArray();
        $items = $comment->getItems();
        foreach ($items as $key => $item) {
            foreach ($statuses as $k => $s) {
                $item["text"] = str_replace("'" . $s['value'] . "'", "'" . $s['label'] . "'", $item["text"]);
            }
        }

        return $items;
    }
}
