/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */

define([
    'Magento_Weee/js/view/checkout/summary/item/price/weee'
], function (weee) {
    'use strict';

    return weee.extend({
        defaults: {
            template: 'Fef_CustomVoucherPoint/checkout/summary/item/price/row_incl_tax',
            displayArea: 'row_incl_tax'
        },

        /**
         * @param {Object} item
         * @return {Number}
         */
        getFinalRowDisplayPriceInclTax: function (item) {
            var rowTotalInclTax = parseFloat(item['row_total_incl_tax']);

            if (!window.checkoutConfig.getIncludeWeeeFlag) {
                rowTotalInclTax += this.getRowWeeeTaxInclTax(item);
            }

            return rowTotalInclTax;
        },

        /**
         * @param {Object} item
         * @return {Number}
         */
        getRowDisplayPriceInclTax: function (item) {
            var rowTotalInclTax = parseFloat(item['row_total_incl_tax']);

            if (window.checkoutConfig.getIncludeWeeeFlag) {
                rowTotalInclTax += this.getRowWeeeTaxInclTax(item);
            }

            return rowTotalInclTax;
        },

        /**
         * @param {Object}item
         * @return {Number}
         */
        getRowWeeeTaxInclTax: function (item) {
            var totalWeeeTaxInclTaxApplied = 0,
                weeeTaxAppliedAmounts;

            if (item['weee_tax_applied']) {
                weeeTaxAppliedAmounts = JSON.parse(item['weee_tax_applied']);
                weeeTaxAppliedAmounts.forEach(function (weeeTaxAppliedAmount) {
                    totalWeeeTaxInclTaxApplied += parseFloat(Math.max(weeeTaxAppliedAmount['row_amount_incl_tax'], 0));
                });
            }

            return totalWeeeTaxInclTaxApplied;
        },

        /**
         *
         * @param item
         * @returns {number}
         */
        getPriceInclTax: function (item) {
            return parseFloat(item['price_incl_tax']);
        },

        /**
         *
         * @param item
         * @returns {boolean|number}
         */
        isDisplayOldPrice: function (item) {
            var old_price;

            if (item.hasOwnProperty('extension_attributes') && item.extension_attributes.hasOwnProperty('old_price')) {
                old_price = item.extension_attributes.old_price;
            } else {
                old_price = item['old_price'];
            }

            if (old_price > item['price_incl_tax'])
                return parseFloat(item['old_price']);
            else
                return false;
        },
        isHasDiscount: function (item) {
            if (item["discount_amount"] != undefined && item["discount_amount"] > 0) { 
                return true;
            }
            return false;
        }
    });
});
