define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/model/quote',
        'Fef_CustomVoucherPoint/js/action/checkout/cart/totals' // Our custom script
    ],
    function($, ko ,quote, totals) {
        'use strict';
        var isLoading = ko.observable(false);

        return function (paymentMethod) {
            quote.paymentMethod(paymentMethod);
            totals(isLoading, paymentMethod['method']);
        }
    }
);