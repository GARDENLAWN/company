<?php

namespace GardenLawn\Company\Model;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Options tree for "Categories" field
 */
class Customers implements OptionSourceInterface
{
    /**
     * @var CustomerCollectionFactory
     */
    protected CustomerCollectionFactory $customerCollectionFactory;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var array
     */
    protected array $customerTree;

    /**
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param RequestInterface $request
     */
    public function __construct(
        CustomerCollectionFactory $customerCollectionFactory,
        RequestInterface          $request
    )
    {
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->request = $request;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray(): array
    {
        return $this->getCustomerTree();
    }

    /**
     * Retrieve categories tree
     *
     * @return array
     */
    protected function getCustomerTree(): array
    {
        $collection = $this->customerCollectionFactory->create();

        $collection->addNameToSelect();

        foreach ($collection as $customer) {
            $customerId = $customer->getEntityId();
            if (!isset($customerById[$customerId])) {
                $customerById[$customerId] = [
                    'value' => $customerId
                ];
            }
            $customerById[$customerId]['label'] = $customer->getName();
        }

        $customerById[-1] = ['value' => '', 'label' => 'Select customer...'];
        asort($customerById);

        $this->customerTree = $customerById;

        return $this->customerTree;
    }
}
