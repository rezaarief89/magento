define([
    'jquery',
    'mage/utils/wrapper',
    'Magento_Checkout/js/model/quote',
    'Smartosc_Checkout/js/helper/data',
], function ($, wrapper, quote, dataHelper) {
    'use strict';

    return function (setShippingInformationAction) {

        return wrapper.wrap(setShippingInformationAction, function (originalAction) {
            var shippingAddress, deliveryDate, deliveryNote, deliveryTimeslot, deliveryStairs;

            shippingAddress = quote.shippingAddress();
            deliveryDate = dataHelper.getObservableValue('checkout.steps.delivery-step.deliveryContent.delivery_date.delivery_date');
            deliveryNote = dataHelper.getObservableValue('checkout.steps.delivery-step.deliveryContent.delivery_note.delivery_note');

            //RAW
            deliveryTimeslot = dataHelper.getObservableValue('checkout.steps.delivery-step.deliveryContent.delivery_slot.delivery_slot');
            deliveryStairs = dataHelper.getObservableValue('checkout.steps.delivery-step.deliveryContent.delivery_stairs.delivery_stairs');

            if (shippingAddress['customAttributes'] === undefined) {
                shippingAddress['customAttributes'] = {};
            }

            if (deliveryDate) {
                shippingAddress['customAttributes']['delivery_date'] = dataHelper.displayDate(deliveryDate);
            }

            if (deliveryNote) {
                shippingAddress['customAttributes']['delivery_note'] = deliveryNote;
            }

            console.log("deliveryTimeslot : "+deliveryTimeslot);
            if (deliveryTimeslot) {
                shippingAddress['customAttributes']['delivery_timeslot'] = deliveryTimeslot;
            }

            if (deliveryStairs) {
                shippingAddress['customAttributes']['delivery_stairs'] = deliveryStairs;
            }

            if (shippingAddress['extension_attributes'] === undefined) {
                shippingAddress['extension_attributes'] = {};
            }

            if ($('.shipping-address-container .gift-card-container #gift-card').is(':checked')) {
                shippingAddress['customAttributes']['gift_message'] = $('.shipping-address-container .smart-gift #gift-message').val();
            }

            if ($('.checkout-shipping-method #shipping-method-checkbox').is(':checked')) {
                shippingAddress['customAttributes']['authorize_message'] = window.authorizeMessage;
            }

            
            $.ajax({

                url: '/fef_shipping/ajax/getcost',
                processData: false,
                contentType: false,
                showLoader: true,
                type: 'POST',
                dataType: 'json',
                success: function (response) {
                    console.log(response);
                    if (response["success"] == true) {
                        var obj = $.parseJSON(response["costData"]);
                        shippingAddress['customAttributes']['cost_weight'] = obj["cost_weight"];
                        shippingAddress['customAttributes']['cost_staircase'] = obj["cost_staircase"];
                        shippingAddress['customAttributes']['cost_location'] = obj["cost_location"];
                        shippingAddress['customAttributes']['voucher_name'] = "Voucher Name : " + obj["voucher_name"];
                        shippingAddress['customAttributes']['voucher_amount'] = "Voucher Type : " + obj["voucher_amount"];
                    } else { 
                        shippingAddress['customAttributes']['cost_weight'] = 0;
                        shippingAddress['customAttributes']['cost_staircase'] = 0;
                        shippingAddress['customAttributes']['cost_location'] = 0;
                        shippingAddress['customAttributes']['voucher_name'] = "Voucher Name : -";
                        shippingAddress['customAttributes']['voucher_amount'] = "Voucher Type : 0";
                    }
                    $('.cost-information .cost-to .shipping-information-content').load(self);
                },
                fail: function (response) {
                    console.log("failed");
                    console.log(response);
                }
            });


            if(window.pickupTab) {
                var pickupDate = $("input[name='pickup_date']").val();
                pickupDate = dataHelper.formatDate(pickupDate);
                shippingAddress['extension_attributes']['pickup_date'] = pickupDate;
                shippingAddress['extension_attributes']['pickup_time'] = $("span[name='pickup_time']").html();
                shippingAddress['extension_attributes']['pickup_comments'] = $("textarea[name='pickup_comments']").val();
                shippingAddress['extension_attributes']['pickup_store_name'] = $(".store-name").text();
                shippingAddress['extension_attributes']['pickup_store_address'] = $(".store-address").text();
                shippingAddress['extension_attributes']['pickup_store_state'] = $(".store-state").text();
                shippingAddress['extension_attributes']['pickup_store_zip'] = $(".store-zip").text();
                shippingAddress['extension_attributes']['billing_floor'] = dataHelper.getObservableValue('checkout.steps.shipping-step.shippingAddress.billingAddress.address-fieldset.floor');
                shippingAddress['extension_attributes']['billing_building'] = dataHelper.getObservableValue('checkout.steps.shipping-step.shippingAddress.billingAddress.address-fieldset.building');
                shippingAddress['customAttributes']['pickup_time'] = $("span[name='pickup_time']").html();
                if ($('.store-container .gift-card-container #gift-card').is(':checked')) {
                    shippingAddress['customAttributes']['gift_message'] = $('.store-container .smart-gift #gift-message').val();
                }
            } else {
                if (!$("#shipping-address-same-as-billing").is(":checked")) {
                    shippingAddress['prefix'] = $('select[name="prefix"]', "#shipping-new-address-form").val();
                }
            }

            return originalAction();
        });
    };
});
