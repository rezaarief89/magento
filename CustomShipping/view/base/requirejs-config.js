var config = {
    config: {
        mixins: {
            'Magento_Checkout/js/action/set-shipping-information': {
                'Smartosc_Checkout/js/mixin/setShippingInformationActionMixin': false,
                'Fef_CustomShipping/js/mixin/setShippingInformationActionMixin': true
            },
            'Magento_Checkout/js/model/shipping-save-processor/payload-extender': {
                'Smartosc_Checkout/js/model/shipping-save-processor/payload-extender-mixin': false,
                'Fef_CustomShipping/js/model/shipping-save-processor/payload-extender-mixin': true
            },
            'Magento_Checkout/js/view/shipping-information': {
                'Smartosc_Checkout/js/view/shipping-information': false,
                'Fef_CustomShipping/js/view/shipping-information': true
            },
        }
    }
};
