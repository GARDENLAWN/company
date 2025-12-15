<?php

namespace GardenLawn\Company\Block\Adminhtml\Grid\Edit;

use GardenLawn\Company\Model\CustomerGroups;
use GardenLawn\Company\Model\Customers;
use GardenLawn\Company\Model\Config\Source\Status;
use IntlDateFormatter;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Cms\Model\Wysiwyg\Config;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;

/**
 * Adminhtml Add New Row Form.
 */
class Form extends Generic
{
    /**
     * @var Store
     */
    protected Store $_systemStore;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Config $wysiwygConfig
     * @param Status $statusOptions
     * @param Customers $customerOptions
     * @param CustomerGroups $customerGroupOptions
     * @param array $data
     */
    public function __construct(
        Context        $context,
        Registry       $registry,
        FormFactory    $formFactory,
        Config         $wysiwygConfig,
        Status         $statusOptions,
        Customers      $customerOptions,
        CustomerGroups $customerGroupOptions,
        array          $data = []
    )
    {
        $this->_statusOptions = $statusOptions;
        $this->_customerOptions = $customerOptions;
        $this->_customerGroupOptions = $customerGroupOptions;
        $this->_wysiwygConfig = $wysiwygConfig;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * Prepare form.
     *
     * @return $this
     * @throws LocalizedException
     */
    protected function _prepareForm(): static
    {
        $dateFormat = $this->_localeDate->getDateFormat(IntlDateFormatter::SHORT);
        $model = $this->_coreRegistry->registry('row_data');
        $form = $this->_formFactory->create(
            ['data' => [
                'id' => 'edit_form',
                'enctype' => 'multipart/form-data',
                'action' => $this->getData('action'),
                'method' => 'post'
            ]
            ]
        );

        $form->setHtmlIdPrefix('gardenlawncompany_');
        if ($model->getCompanyId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Edit Row Data'), 'class' => 'fieldset-wide']
            );
            $fieldset->addField('company_id', 'hidden', ['name' => 'company_id']);
        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => __('Add Row Data'), 'class' => 'fieldset-wide']
            );
        }

        $fieldset->addField(
            'customer_group_id',
            'select',
            [
                'name' => 'customer_group_id',
                'label' => __('Group'),
                'id' => 'customer_group_id',
                'title' => __('Group'),
                'values' => $this->_customerGroupOptions->toOptionArray(),
                'class' => 'groups',
                'required' => false
            ]
        );

        $fieldset->addField(
            'status',
            'select',
            [
                'name' => 'status',
                'label' => __('Status'),
                'id' => 'status',
                'title' => __('Status'),
                'values' => $this->_statusOptions->getOptionArray(),
                'class' => 'status',
                'required' => false
            ]
        );

        $fieldset->addField(
            'comment',
            'editor',
            [
                'name' => 'comment',
                'label' => __('Comment'),
                'required' => false
            ]
        );

        /*$fieldset->addField(
            'created_at',
            'date',
            [
                'name' => 'created_at',
                'label' => __('Created at'),
                'date_format' => $dateFormat,
                'time_format' => 'HH:mm:ss',
                'class' => 'validate-date validate-date-range date-range-custom_theme-from required-entry',
                'style' => 'width:200px'
            ]
        );

        $fieldset->addField(
            'updated_at',
            'date',
            [
                'name' => 'updated_at',
                'label' => __('Updated at'),
                'date_format' => $dateFormat,
                'time_format' => 'HH:mm:ss',
                'class' => 'validate-date validate-date-range date-range-custom_theme-from required-entry',
                'style' => 'width:200px'
            ]
        );*/

        /*$wysiwygConfig = $this->_wysiwygConfig->getConfig(['tab_id' => $this->getTabId()]);

        $fieldset->addField(
            'content',
            'editor',
            [
                'name' => 'content',
                'label' => __('Content'),
                'style' => 'height:36em;',
                'required' => true,
                'config' => $wysiwygConfig
            ]
        );*/

        $fieldset->addField(
            'nip',
            'text',
            [
                'name' => 'nip',
                'label' => __('NIP'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Name'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'ceidg_email',
            'text',
            [
                'name' => 'ceidg_email',
                'label' => __('CEIDG Email'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'ceidg_phone',
            'text',
            [
                'name' => 'ceidg_phone',
                'label' => __('CEIDG Phone'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'email',
            'text',
            [
                'name' => 'email',
                'label' => __('Email'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'phone',
            'text',
            [
                'name' => 'phone',
                'label' => __('Phone'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'www',
            'text',
            [
                'name' => 'www',
                'label' => __('Www'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'address',
            'text',
            [
                'name' => 'address',
                'label' => __('Address'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'distance',
            'text',
            [
                'name' => 'distance',
                'label' => __('Distance'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'url',
            'text',
            [
                'name' => 'url',
                'label' => __('Url'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'customer_id',
            'select',
            [
                'name' => 'customer_id',
                'label' => __('Customer'),
                'id' => 'customer_id',
                'title' => __('Customer'),
                'values' => $this->_customerOptions->toOptionArray(),
                'class' => 'customers',
                'required' => false
            ]
        );

        $form->setValues($model->getData());
        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
