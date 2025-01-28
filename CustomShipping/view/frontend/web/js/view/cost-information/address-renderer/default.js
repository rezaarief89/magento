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
            template: 'Fef_CustomShipping/cost-information/address-renderer/default'
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

        getCostWeight: function () { 
            var costValue = this.getPureValue("cost_weight");
            return "Cost Weight : " + this.getFormattedPrice(costValue);
        },

        getCostLocation: function () { 
            var costValue = this.getPureValue("cost_location");
            return "Cost Location : " + this.getFormattedPrice(costValue);
        },

        getCostStairCase: function () { 
            var costValue = this.getPureValue("cost_staircase");
            return "Cost Staircase : " + this.getFormattedPrice(costValue);
        },

        hideCostWeight: function () { 
            var costValue = this.getPureValue("cost_weight");
            if (costValue == 0) { 
                return false;
            }
            return true;
        },

        hideCostStairCase: function () { 
            var costValue = this.getPureValue("cost_staircase");
            if (costValue == 0) { 
                return false;
            }
            return true;
        },

        hideCostLocation: function () { 
            var costValue = this.getPureValue("cost_location");
            if (costValue == 0) { 
                return false;
            }
            return true;
        }        
    });
});
