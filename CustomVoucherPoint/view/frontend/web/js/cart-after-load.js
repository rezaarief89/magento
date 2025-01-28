define([
    'jquery',
    'Magento_Customer/js/model/customer',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/cart/totals'
], function ($, customer, quote, totals) {
    'use strict';

    return function (config) {
        $(document).ready(function () {
            // Ensure that Magento UI components are loaded
            require(['Magento_Ui/js/lib/view/utils/async'], function (async) {
                async.async([
                    'Magento_Checkout/js/view/cart/totals'
                ]).done(function () {
                    // Your custom code here
                    console.log('Cart page has fully loaded');

                    // Example: Accessing the cart totals
                    var cartTotals = totals.getTotals();
                    console.log(cartTotals);

                    // You can perform other actions here
                });
            });
        });
    };
});
