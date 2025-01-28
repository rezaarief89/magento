//Smartosc/Checkout/view/frontend/web/js/model/shipping-save-processor/payload-extender-mixin.js

define([
    'jquery',
    'mage/utils/wrapper',
    'Smartosc_Checkout/js/helper/data'
], function ($, wrapper, dataHelper) {
    'use strict';

    return function (payloadExtender) {
        return wrapper.wrap(payloadExtender, function (originalAction, payload) {
            payload = originalAction(payload);
            var extensionAttributes = payload.addressInformation.extension_attributes;
            extensionAttributes = $.extend({}, extensionAttributes);

            var shipping_type = $('#shipping>.checkout-shipping-type>div.active').text().trim().toLowerCase();

            var billingFloor = dataHelper.getObservableValue('checkout.steps.shipping-step.shippingAddress.billingAddress.address-fieldset.floor');
            var billingBuilding = dataHelper.getObservableValue('checkout.steps.shipping-step.shippingAddress.billingAddress.address-fieldset.building');
            var shippingFloor = dataHelper.getObservableValue('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.floor');
            var shippingBuilding = dataHelper.getObservableValue('checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.building');

            if (billingFloor !== "") {
                extensionAttributes['billing_floor'] = billingFloor;
            }
            if (billingBuilding !== "") {
                extensionAttributes['billing_building'] = billingBuilding;
            }
            var useBillingAShippingAddress = jQuery("input[id='shipping-address-same-as-billing']").is(':checked');

            if (!useBillingAShippingAddress) {
                if (shippingFloor !== "") {
                    extensionAttributes['shipping_floor'] = shippingFloor;
                }
                if (shippingBuilding !== "") {
                    extensionAttributes['shipping_building'] = shippingBuilding;
                }
            } else {
                extensionAttributes['shipping_floor'] = billingFloor;
                extensionAttributes['shipping_building'] = billingBuilding;
            }

            extensionAttributes['accept_authorize'] = null;
            extensionAttributes['authorize_message'] = window.authorizeMessage;

            // store shipping type in extension attributes
            payload.addressInformation.extension_attributes['shipping_type'] = (shipping_type === 'delivery') ? 'delivery': 'in_store_pickup';

            if (shipping_type === 'delivery') {
                var delivery_date = dataHelper.getObservableValue('checkout.steps.delivery-step.deliveryContent.delivery_date.delivery_date');
                var delivery_note = dataHelper.getObservableValue('checkout.steps.delivery-step.deliveryContent.delivery_note.delivery_note');
                var delivery_timeslot = dataHelper.getObservableValue('checkout.steps.delivery-step.deliveryContent.delivery_slot.delivery_slot');
                var delivery_stairs = dataHelper.getObservableValue('checkout.steps.delivery-step.deliveryContent.delivery_stairs.delivery_stairs');

                if (delivery_date !== "") {
                    delivery_date = dataHelper.formatDate(delivery_date);
                    extensionAttributes['delivery_date'] = delivery_date;
                }

                if (delivery_note !== "") {
                    extensionAttributes['delivery_note'] = delivery_note;
                }
                //RAW
                console.log("delivery_timeslot : "+delivery_timeslot);
                if (delivery_timeslot !== "") {
                    extensionAttributes['delivery_timeslot'] = delivery_timeslot;
                }
                if (delivery_stairs !== "") {
                    extensionAttributes['delivery_stairs'] = delivery_stairs;
                }

                extensionAttributes['cost_weight'] = 0;
                extensionAttributes['cost_location'] = 0;
                extensionAttributes['cost_item_spesific'] = 0;
                extensionAttributes['cost_staircase'] = 0;
                extensionAttributes['cost_delivery_type'] = 0;
                extensionAttributes['voucher_name'] = "";
                extensionAttributes['voucher_amount'] = 0;

                if ($('.shipping-address-container #gift-card').is(':checked')) {
                    extensionAttributes['gift_message_from'] = $('.shipping-address-container .smart-gift input#gift-from').val();
                    extensionAttributes['gift_message_to'] = $('.shipping-address-container .smart-gift input#gift-to').val();
                    extensionAttributes['gift_message'] = $('.shipping-address-container .smart-gift #gift-message').val();
                }

                if ($('.checkout-shipping-method #shipping-method-checkbox').is(':checked')) {
                    extensionAttributes['accept_authorize'] = 1;
                }

                payload.addressInformation.extension_attributes = $.extend(
                    payload.addressInformation.extension_attributes,
                    extensionAttributes
                );
            } else if (shipping_type === "in store pickup") {
                var pickup_date = dataHelper.getObservableValue('checkout.steps.shipping-step.shippingAddress.delivery-extra-information.pickup_date');
                pickup_date = dataHelper.formatDate(pickup_date);
                var pickup_note = dataHelper.getObservableValue('checkout.steps.shipping-step.shippingAddress.delivery-extra-information.pickup_comments');
                var pickup_time = dataHelper.getObservableValue('checkout.steps.shipping-step.shippingAddress.delivery-extra-information.pickup_time');
                var pickup_store_name = $(".store-name").text();
                var pickup_store_address = $(".store-address").text();
                var pickup_store_state = $(".store-state").text();
                var pickup_store_zip = $(".store-zip").text();

                if (billingBuilding !== "") {
                    extensionAttributes['billing_building'] = billingBuilding;
                }
                if (billingFloor !== "") {
                    extensionAttributes['billing_floor'] = billingFloor;
                }
                if (pickup_date !== "") {
                    extensionAttributes['pickup_date'] = pickup_date;
                }
                if (pickup_note !== "") {
                    extensionAttributes['pickup_comments'] = pickup_note;
                }
                if (pickup_time !== "") {
                    extensionAttributes['pickup_time'] = pickup_time;
                }
                if (pickup_store_name !== "") {
                    extensionAttributes['pickup_store_name'] = pickup_store_name;
                }
                if (pickup_store_address !== "") {
                    extensionAttributes['pickup_store_address'] = pickup_store_address;
                }
                if (pickup_store_state !== "") {
                    extensionAttributes['pickup_store_state'] = pickup_store_state;
                }
                if (pickup_store_zip !== "") {
                    extensionAttributes['pickup_store_zip'] = pickup_store_zip;
                }

                if ($('.store-container #gift-card').is(':checked')) {
                    extensionAttributes['gift_message_from'] = $('input[name="gift-from"]').val();
                    extensionAttributes['gift_message_to'] = $('input[name="gift-to"]').val();
                    extensionAttributes['gift_message'] = $('textarea[name="gift-message"]').val();
                }

                payload.addressInformation.shipping_address.extension_attributes = $.extend(
                    payload.addressInformation.shipping_address.extension_attributes,
                    extensionAttributes
                );
            }

            return payload;
        });
    };
});
