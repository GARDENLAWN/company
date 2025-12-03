<?php

namespace GardenLawn\Company\Ui\Component\Listing\Grid\Column;

use GardenLawn\Company\Model\CustomerGroups;
use GardenLawn\Company\Model\Status;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Column;

class Action extends Column
{
    const string ROW_EDIT_URL = 'company/grid/addrow';
    const string ROW_CUSTOMER_CREATE_URL = 'company/grid/createcustomer';
    const string ROW_CUSTOMER_SEND_EMAIL_URL = 'company/grid/sendemail';
    protected UrlInterface $_urlBuilder;
    private string $_editUrl;
    private string $_customerCreateUrl;
    private string $_sendEmailUrl;
    private CustomerGroups $customerGroups;

    public function __construct(
        ContextInterface   $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface       $urlBuilder,
        CustomerGroups     $customerGroups,
        array              $components = [],
        array              $data = [],
        string             $editUrl = self::ROW_EDIT_URL,
        string             $customerCreateUrl = self::ROW_CUSTOMER_CREATE_URL,
        string             $sendEmailUrl = self::ROW_CUSTOMER_SEND_EMAIL_URL
    )
    {
        $this->_urlBuilder = $urlBuilder;
        $this->_editUrl = $editUrl;
        $this->_customerCreateUrl = $customerCreateUrl;
        $this->_sendEmailUrl = $sendEmailUrl;
        $this->customerGroups = $customerGroups;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    public function prepareDataSource(array $dataSource): array
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $name = $this->getData('name');
                if (isset($item['company_id'])) {
                    $item['status'] = Status::getStatusName($item['status']);
                    $item['customer_group_id'] = $this->customerGroups->getGroupName($item['customer_group_id']);
                    $item[$name]['edit'] = [
                        'href' => $this->_urlBuilder->getUrl(
                            $this->_editUrl,
                            ['id' => $item['company_id']]
                        ),
                        'label' => __('Edit'),
                    ];
                    if (!$item['customer_id']) {
                        $item[$name]['create_customer'] = [
                            'href' => $this->_urlBuilder->getUrl(
                                $this->_customerCreateUrl,
                                ['id' => $item['company_id']]
                            ),
                            'label' => __('Create customer'),
                        ];
                    }
                    $item[$name]['send_email'] = [
                        'href' => $this->_urlBuilder->getUrl(
                            $this->_sendEmailUrl . '?' . ($item['customer_id'] > 0 ? 'customer_id=' . $item['customer_id'] : 'company_id=' . $item['company_id'])
                        ),
                        'label' => __('Send email'),
                    ];
                }
            }
        }

        return $dataSource;
    }
}
