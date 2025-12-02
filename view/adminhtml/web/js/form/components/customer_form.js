define([
    'Magento_Ui/js/form/form',
    'Magento_Ui/js/modal/confirm',
    'jquery'
], function (FormComponent, confirm, $) {
    'use strict';

    return FormComponent.extend({
        forceConfirm: function (url) {
            confirm({
                title: $.mage.__('Confirm Account'),
                content: $.mage.__('Are you sure you want to manually confirm this customer account?'),
                actions: {
                    confirm: function () {
                        $.ajax({
                            url: url,
                            type: 'POST',
                            dataType: 'json',
                            showLoader: true,
                            success: function (response) {
                                if (response.success) {
                                    window.location.reload();
                                } else {
                                    alert(response.message);
                                }
                            },
                            error: function () {
                                alert($.mage.__('An error occurred while confirming the account.'));
                            }
                        });
                    }
                }
            });
        }
    });
});
