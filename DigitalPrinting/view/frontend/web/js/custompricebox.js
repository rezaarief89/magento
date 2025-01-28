define([
    'jquery',
    "Magento_Ui/js/modal/modal"
], function ($, modal) {
    'use strict';
    var widgetMixin = {
        reloadPrice: function() {
            return this._super();
        }
    };
    return function (parentWidget) {
        $.widget('mage.priceBox', parentWidget, widgetMixin);
        return $.mage.priceBox;
    };
});