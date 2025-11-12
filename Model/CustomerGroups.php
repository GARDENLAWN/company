<?php

namespace GardenLawn\Company\Model;

use Magento\Customer\Model\ResourceModel\Group\CollectionFactory as CustomerGroupsCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;

class CustomerGroups implements OptionSourceInterface
{
    protected CustomerGroupsCollectionFactory $customerGroupCollectionFactory;
    protected RequestInterface $request;
    protected array $customerGroupTree;

    public function __construct(
        CustomerGroupsCollectionFactory $customerGroupCollectionFactory,
        RequestInterface                $request
    )
    {
        $this->customerGroupCollectionFactory = $customerGroupCollectionFactory;
        $this->request = $request;

        $collection = $this->customerGroupCollectionFactory->create();
        $customerGroupById = [];

        foreach ($collection as $customerGroup) {
            $customerGroupId = $customerGroup->getCustomerGroupId();
            if (!isset($customerGroupById[$customerGroupId])) {
                $customerGroupById[$customerGroupId] = [
                    'value' => $customerGroupId
                ];
            }
            $customerGroupById[$customerGroupId]['label'] = $customerGroup->getCustomerGroupCode();
        }

        $this->customerGroupTree = $customerGroupById;
    }

    public function toOptionArray(): array
    {
        return $this->getCustomerGroupTree();
    }

    public function getGroupName(?int $groupId): string
    {
        $i = $this->customerGroupTree;
        return $this->customerGroupTree[$groupId ?? 0]['label'];
    }

    protected function getCustomerGroupTree(): array
    {
        $customerGroupById = $this->customerGroupTree;

        $customerGroupById[-1] = ['value' => '', 'label' => 'Select group...'];
        asort($customerGroupById);

        return $customerGroupById;
    }
}
