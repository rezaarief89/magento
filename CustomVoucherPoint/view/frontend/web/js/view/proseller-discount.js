/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Smartosc_Checkout/js/model/step-navigator',
    'Magento_Checkout/js/model/sidebar'
], function ($, Component, quote, stepNavigator, sidebarModel) {
    'use strict';
    return Component.extend({
        defaults: {
            template: 'Fef_CustomVoucherPoint/proseller-discount'
        },

        /**
         * @return {Boolean}
         */
        isVisible: function () {
            return !quote.isVirtual() && stepNavigator.isProcessed('shipping');
        },

        /**
         * Back step.
         */
        back: function () {
            sidebarModel.hide();
            stepNavigator.navigateTo('shipping');
        },

        /**
         * Back to shipping method.
         */
        backToShippingMethod: function () {
            sidebarModel.hide();
            stepNavigator.navigateTo('shipping', 'opc-shipping_method');
        },

        getPureValue: function (type) { 
            var shippingAddress = quote.shippingAddress();
            var extAttr = shippingAddress.customAttributes;
            if (extAttr[type] != undefined) {
                return extAttr[type];
            } else { 
                return 0;
            }
            // return extAttr[type];
        },

        hideTitle: function () { 
            var discName = this.getPureValue("voucher_name");
            var discValue = this.getPureValue("voucher_amount");

            if ((discName == "Voucher Name : -" || discName == "Voucher Name : ") && (discValue == "Voucher Type : 0" || discValue == "Voucher Type : $ 0.00")) { 
                return false;
            }
            return true;
        }
    });
});
