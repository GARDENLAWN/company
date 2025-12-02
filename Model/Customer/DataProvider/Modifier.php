<?php
declare(strict_types=1);

namespace GardenLawn\Company\Model\Customer\DataProvider;

use Exception;
use Magento\Ui\DataProvider\Modifier\ModifierInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use GardenLawn\Company\Helper\Data as CompanyHelper;
use Magento\Framework\UrlInterface;

class Modifier implements ModifierInterface
{
    private CompanyHelper $companyHelper;
    private RequestInterface $request;
    private CustomerRepositoryInterface $customerRepository;
    private UrlInterface $urlBuilder;

    public function __construct(
        CompanyHelper $companyHelper,
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        UrlInterface $urlBuilder
    ) {
        $this->companyHelper = $companyHelper;
        $this->request = $request;
        $this->customerRepository = $customerRepository;
        $this->urlBuilder = $urlBuilder;
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

            if ($customer->getConfirmation()) {
                $confirmUrl = $this->urlBuilder->getUrl('customer/index/confirm', ['id' => $customerId]);
                $meta['account']['children']['force_confirm_button'] = [
                    'arguments' => [
                        'data' => [
                            'config' => [
                                'formElement' => 'container',
                                'componentType' => 'container',
                                'component' => 'Magento_Ui/js/form/components/button',
                                'title' => __('Force Confirm Account'),
                                'actions' => [
                                    [
                                        'targetName' => 'customer_form.customer_form',
                                        'actionName' => 'forceConfirm',
                                        'params' => [
                                            $confirmUrl
                                        ]
                                    ]
                                ],
                                'sortOrder' => 25,
                            ],
                        ],
                    ],
                ];
            }
        } catch (Exception) {
            // Customer not found or other error, do nothing to the meta
        }

        return $meta;
    }
}
