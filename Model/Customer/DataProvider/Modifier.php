<?php
declare(strict_types=1);

namespace GardenLawn\Company\Model\Customer\DataProvider;

use Exception;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use GardenLawn\Company\Helper\Data as CompanyHelper;

class Modifier implements ModifierInterface
{
    private CompanyHelper $companyHelper;
    private RequestInterface $request;
    private CustomerRepositoryInterface $customerRepository;

    public function __construct(
        CompanyHelper $companyHelper,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository
    ) {
        $this->companyHelper = $companyHelper;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
    }

    public function modifyData(array $data): array
    {
        return $data;
    }

    public function modifyMeta(array $meta): array
    {
        $customerId = $this->request->getParam('id');
        if (!$customerId) {
            return $meta;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $groupId = (int)$customer->getGroupId();

            if (in_array($groupId, $this->companyHelper->getB2bCustomerGroups())) {
                $meta['addresses']['children'] = [
                    'b2b_message' => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'formElement' => 'container',
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

            $meta['account']['children']['force_confirm'] = [
                'arguments' => [
                    'data' => [
                        'config' => [
                            'label' => __('Force Email Confirmation'),
                            'componentType' => 'field',
                            'formElement' => 'checkbox',
                            'dataType' => 'boolean',
                            'dataScope' => 'force_confirm', // This was missing
                            'prefer' => 'toggle',
                            'valueMap' => [
                                'true' => '1',
                                'false' => '0'
                            ],
                            'default' => '0',
                            'sortOrder' => 25, // After "Send Welcome Email"
                        ],
                    ],
                ],
            ];
        } catch (Exception) {
            // Customer not found or other error, do nothing to the meta
        }

        return $meta;
    }
}
