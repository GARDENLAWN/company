<?php
declare(strict_types=1);

namespace GardenLawn\Company\Model\Customer\DataProvider;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Customer\DataProviderWithDefaultAddresses;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\Component\Form\Fieldset;
use GardenLawn\Company\Helper\Data as CompanyHelper;

class Modifier extends \Magento\Customer\Model\Customer\DataProvider\Modifier
{
    private CompanyHelper $companyHelper;
    private RequestInterface $request;

    public function __construct(
        DataProviderWithDefaultAddresses $dataProvider,
        CompanyHelper $companyHelper,
        RequestInterface $request
    ) {
        parent::__construct($dataProvider);
        $this->companyHelper = $companyHelper;
        $this->request = $request;
    }

    public function modifyData(array $data)
    {
        return parent::modifyData($data);
    }

    public function modifyMeta(array $meta)
    {
        $customerId = $this->request->getParam('id');
        if (!$customerId) {
            return $meta;
        }

        $customer = $this->dataProvider->getCustomer($customerId);
        $groupId = (int)$customer->getGroupId();

        if (in_array($groupId, $this->companyHelper->getB2bCustomerGroups())) {
            // Hide address fields and show a message
            $meta['addresses']['children'] = [
                'b2b_message' => [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/html',
                                'additionalClasses' => 'admin__fieldset-note',
                                'content' => __('The billing address for this B2B customer is synchronized with CEIDG. The shipping address can be managed from the customer\'s account in the storefront.'),
                            ],
                        ],
                    ],
                ],
            ];
        }

        return $meta;
    }
}
