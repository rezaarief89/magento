/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
    'Magento_Checkout/js/view/summary/abstract-total',
    'underscore',
    'Magento_Customer/js/customer-data',
    'Smartosc_Checkout/js/helper/data',
    'Magento_Checkout/js/model/totals',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/view/summary/grand-total',
], function (Component, _, customerData, dataHelper, totals, quote, grandTotal) {
    'use strict';


    return Component.extend({
        defaults: {
            template: 'Fef_OverrideSummaryCheckout/checkout/summary/reward-discount'
        },

        getPureValue: function () { 
            var totals = quote.getTotals()();
            var totalSegments = totals["total_segments"];

            var filteredValue = totalSegments.filter(function (item) {
                    return item.code == "zoku_point";
            });

            var price = 0;
            if (filteredValue[0] != undefined) { 
                price = filteredValue[0]["value"];
            }
            
            return price;
        },

        /**
         * @return {*|String}
         */
        getValue: function () {
            return "- "+this.getFormattedPrice(this.getPureValue());
        }
    });
});
