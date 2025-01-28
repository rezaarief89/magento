/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'underscore',
    'Magento_Customer/js/customer-data',
    'Smartosc_Checkout/js/helper/data',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/quote',
], function (Component, _, customerData, dataHelper, totals, quote) {

    'use strict';
    return Component.extend({
        defaults: {
            template: 'Fef_CustomVoucherPoint/proseller-discount/address-renderer/default'
        },
        
        initialize: function () {
            this._super();
            return this;
        },

        getPureValue: function (type) { 
            var shippingAddress = quote.shippingAddress();
            var extAttr = shippingAddress.customAttributes;
            return extAttr[type];
        },

        getDiscountName: function () { 
            var discName = this.getPureValue("voucher_name");
            return discName;
        },

        getDiscountValue: function () { 
            var discValue = this.getPureValue("voucher_amount");
            return discValue;
        },

        hideDiscountName: function () { 
            var costValue = this.getPureValue("voucher_name");
            if (costValue == "Voucher Name : -" || costValue == "Voucher Name : ") { 
                return false;
            }
            return true;
        },

        hideDiscountValue: function () { 
            var costValue = this.getPureValue("voucher_amount");
            if (costValue == "Voucher Type : 0" || costValue == "Voucher Type : $ 0.00") { 
                return false;
            }
            return true;
        }
    });
});
