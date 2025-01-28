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
            template: 'Fef_CustomShipping/cost-information'
        },

        /**
         * @return {Boolean}
         */
        isVisible: function () {
            return !quote.isVirtual() && stepNavigator.isProcessed('shipping');
        },

        /**
         * @return {String}
         */
        getShippingMethodTitle: function () {
            var shippingMethod = quote.shippingMethod(),
                shippingMethodTitle = '';

            if (!shippingMethod) {
                return '';
            }

            shippingMethodTitle = shippingMethod['carrier_title'];

            if (typeof shippingMethod['method_title'] !== 'undefined') {
                shippingMethodTitle += ' - ' + shippingMethod['method_title'];
            }

            return shippingMethodTitle;
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

        hideTitle: function () { 
            var costW = this.getPureValue("cost_weight");
            var costSc = this.getPureValue("cost_staircase");
            var costL = this.getPureValue("cost_location");
            if (costW == 0 && costSc == 0 && costL == 0) {
                return false;
            } else if (costW == undefined && costSc == undefined && costL == undefined) { 
                return false;
            }
            return true;
            
        },

        getPureValue: function (type) { 
            var shippingAddress = quote.shippingAddress();
            var extAttr = shippingAddress.customAttributes;
            if (extAttr[type] != undefined) {
                return extAttr[type];
            } else { 
                return 0;
            }
            
        },
    });
});
