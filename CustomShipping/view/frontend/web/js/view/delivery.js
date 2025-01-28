/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'underscore',
    'Magento_Ui/js/form/form',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/model/address-list',
    'Magento_Checkout/js/model/address-converter',
    'mage/storage',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/model/resource-url-manager',
    'Magento_Checkout/js/action/create-shipping-address',
    'Magento_Checkout/js/action/select-shipping-address',
    'Magento_Checkout/js/model/shipping-rates-validator',
    'Magento_Checkout/js/model/shipping-address/form-popup-state',
    'Magento_Checkout/js/model/shipping-service',
    'Magento_Checkout/js/action/select-shipping-method',
    'Magento_Checkout/js/model/shipping-rate-registry',
    'Magento_Checkout/js/action/set-shipping-information',
    'Smartosc_Checkout/js/model/step-navigator',
    'Smartosc_Checkout/js/model/validate-note-length',
    'Magento_Ui/js/modal/modal',
    'Magento_Checkout/js/model/checkout-data-resolver',
    'Magento_Checkout/js/checkout-data',
    'uiRegistry',
    'mage/translate',
    'Magento_Checkout/js/model/shipping-rate-service',
    'Smartosc_Checkout/js/helper/data'
], function (
    $,
    _,
    Component,
    ko,
    customer,
    addressList,
    addressConverter,
    storage,
    quote,
    resourceUrlManager,
    createShippingAddress,
    selectShippingAddress,
    shippingRatesValidator,
    formPopUpState,
    shippingService,
    selectShippingMethodAction,
    rateRegistry,
    setShippingInformationAction,
    stepNavigator,
    validateNote,
    modal,
    checkoutDataResolver,
    checkoutData,
    registry,
    $t,
    dataHelper
) {
    'use strict';

    var popUp = null;

    var localAction = this;

    return Component.extend({
        defaults: {
            template: 'Fef_CustomShipping/delivery',
            shippingFormTemplate: 'Magento_Checkout/shipping-address/form',
            shippingMethodListTemplate: 'Smartosc_Checkout/shipping-address/shipping-method-list',
            shippingMethodItemTemplate: 'Smartosc_Checkout/shipping-address/shipping-method-item',
            // shippingMethodNoteTemplate: 'Smartosc_Checkout/shipping-address/shipping-method-note'
        },
        visible: ko.observable(!quote.isVirtual()),
        errorValidationMessage: ko.observable(false),
        isCustomerLoggedIn: customer.isLoggedIn,
        isFormPopUpVisible: formPopUpState.isVisible,
        isFormInline: addressList().length === 0,
        isNewAddressAdded: ko.observable(false),
        saveInAddressBook: 1,
        quoteIsVirtual: quote.isVirtual(),

        /**
         * @return {exports}
         */
        initialize: function () {
            var self = this,
                hasNewAddress,
                fieldsetName = 'checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset',
                billingFildsetName = 'checkout.steps.shipping-step.shippingAddress.billingAddress.address-fieldset';

            this._super();

            if (!quote.isVirtual()) {
                stepNavigator.registerStep(
                    'delivery',
                    null,
                    $t('Delivery Methods'),
                    this.visible, _.bind(this.navigate, this),
                    this.sortOrder
                );
            }
            checkoutDataResolver.resolveShippingAddress();

            hasNewAddress = addressList.some(function (address) {
                return address.getType() == 'new-customer-address'; //eslint-disable-line eqeqeq
            });

            this.isNewAddressAdded(hasNewAddress);

            this.isFormPopUpVisible.subscribe(function (value) {
                if (value) {
                    self.getPopUp().openModal();
                }
            });

            quote.shippingMethod.subscribe(function () {
                self.errorValidationMessage(false);
            });

            registry.async('checkoutProvider')(function (checkoutProvider) {
                var shippingAddressData = checkoutData.getShippingAddressFromData();

                if (shippingAddressData) {
                    checkoutProvider.set(
                        'shippingAddress',
                        $.extend(true, {}, checkoutProvider.get('shippingAddress'), shippingAddressData)
                    );
                }
                checkoutProvider.on('shippingAddress', function (shippingAddrsData) {
                    checkoutData.setShippingAddressFromData(shippingAddrsData);
                });
                shippingRatesValidator.initFields(fieldsetName);
                shippingRatesValidator.initFields(billingFildsetName);
            });

            return this;
        },

        validateNoteLimit: function (element, viewModel) {

            var limit = Number(checkoutConfig.delivery_note_limit);

            $(element).keyup(function (event) {
                return validateNote.showErrorMessage(element, limit);
            });

            $(element).keypress(function (event) {
                return validateNote.showErrorMessage(element, limit);
            });
        },

        /**
         * Navigator change hash handler.
         *
         * @param {Object} step - navigation step
         */
        navigate: function (step) {
            step && step.isVisible(true);
        },

        /**
         * @return {*}
         */
        getPopUp: function () {
            var self = this,
                buttons;

            if (!popUp) {
                buttons = this.popUpForm.options.buttons;
                this.popUpForm.options.buttons = [
                    {
                        text: buttons.save.text ? buttons.save.text : $t('Save Address'),
                        class: buttons.save.class ? buttons.save.class : 'action primary action-save-address',
                        click: self.saveNewAddress.bind(self)
                    },
                    {
                        text: buttons.cancel.text ? buttons.cancel.text : $t('Cancel'),
                        class: buttons.cancel.class ? buttons.cancel.class : 'action secondary action-hide-popup',

                        /** @inheritdoc */
                        click: this.onClosePopUp.bind(this)
                    }
                ];

                /** @inheritdoc */
                this.popUpForm.options.closed = function () {
                    self.isFormPopUpVisible(false);
                };

                this.popUpForm.options.modalCloseBtnHandler = this.onClosePopUp.bind(this);
                this.popUpForm.options.keyEventHandlers = {
                    escapeKey: this.onClosePopUp.bind(this)
                };

                /** @inheritdoc */
                this.popUpForm.options.opened = function () {
                    // Store temporary address for revert action in case when user click cancel action
                    self.temporaryAddress = $.extend(true, {}, checkoutData.getShippingAddressFromData());
                };
                popUp = modal(this.popUpForm.options, $(this.popUpForm.element));
            }

            return popUp;
        },

        /**
         * Revert address and close modal.
         */
        onClosePopUp: function () {
            checkoutData.setShippingAddressFromData($.extend(true, {}, this.temporaryAddress));
            this.getPopUp().closeModal();
        },

        /**
         * Show address form popup
         */
        showFormPopUp: function () {
            this.isFormPopUpVisible(true);
        },

        /**
         * Save new shipping address
         */
        saveNewAddress: function () {
            var addressData,
                newShippingAddress;

            this.source.set('params.invalid', false);
            this.triggerShippingDataValidateEvent();

            if (!this.source.get('params.invalid')) {
                addressData = this.source.get('shippingAddress');
                // if user clicked the checkbox, its value is true or false. Need to convert.
                addressData['save_in_address_book'] = this.saveInAddressBook ? 1 : 0;

                // New address must be selected as a shipping address
                newShippingAddress = createShippingAddress(addressData);
                selectShippingAddress(newShippingAddress);
                checkoutData.setSelectedShippingAddress(newShippingAddress.getKey());
                checkoutData.setNewCustomerShippingAddress($.extend(true, {}, addressData));
                this.getPopUp().closeModal();
                this.isNewAddressAdded(true);
            }
        },

        /**
         * Shipping Method View
         */
        rates: shippingService.getShippingRates(),
        isLoading: shippingService.isLoading,
        isSelected: ko.computed(function () {
            return quote.shippingMethod() ?
                quote.shippingMethod()['carrier_code'] + '_' + quote.shippingMethod()['method_code'] :
                null;
        }),

        isAllowNextAction: ko.computed(function () {
            let shippingMethod = quote.shippingMethod();
            $(".table-checkout-shipping-method").removeClass("not-show");

            if ( shippingMethod !== undefined && shippingMethod != null) {
                if ( shippingMethod['carrier_code'] + '_' + shippingMethod['method_code'] === "owsh1_null"
                    && shippingMethod['error_message']
                ){
                    $(".table-checkout-shipping-method").addClass("not-show");
                    return false;
                }
            }
            return true;
        }),

        /**
         * @param {Object} shippingMethod
         * @return {Boolean}
         */
        selectShippingMethod: function (shippingMethod) {
            selectShippingMethodAction(shippingMethod);
            checkoutData.setSelectedShippingRate(shippingMethod['carrier_code'] + '_' + shippingMethod['method_code']);

            return true;
        },

        /**
         * Set shipping information handler
         */
        setShippingInformation: function () {
            console.log("setShippingInformation");
            if (this.validateShippingInformation()) {
                setShippingInformationAction().done(
                    function () {
                        var serviceUrl = resourceUrlManager.getUrlForEstimationShippingMethodsForNewAddress(quote);
                        var address = quote.shippingAddress();
                        var payload = JSON.stringify({
                            address: {
                                'street': address.street,
                                'city': address.city,
                                'region_id': address.region_id,
                                'country_id': address.countryId,
                                'postcode': address.postcode,
                                'email': address.email,
                                'customer_id': address.customer_id,
                                'firstname': address.firstname,
                                'lastname': address.lastname,
                                'middlename': address.middlename,
                                'prefix': address.prefix,
                                'suffix': address.suffix,
                                'vat_id': address.vat_id,
                                'company': address.company,
                                'telephone': address.telephone,
                                'fax': address.fax,
                                'extension_attributes' : address.extension_attributes,
                                'custom_attributes': address.custom_attributes,
                                'save_in_address_book': address.save_in_address_book
                            }
                        });
                        var selectShippingMethod;

                        storage.post(serviceUrl, payload, false).
                            done(function (result) {
                                
                                selectShippingMethod = result.filter(item => item.method_code !== 'in_store_pickup');


                                $.ajax({
                                    url: '/fef_shipping/ajax/getcost',
                                    processData: false,
                                    contentType: false,
                                    showLoader: false,
                                    type: 'POST',
                                    dataType: 'json',
                                    success: function (response) {
                                        console.log(response);
                                        if (response["success"] == true) {
                                            var obj = $.parseJSON(response["costData"]);
                                            address['customAttributes']['cost_weight'] = obj["cost_weight"];
                                            address['customAttributes']['cost_staircase'] = obj["cost_staircase"];
                                            address['customAttributes']['cost_location'] = obj["cost_location"];
                                            address['customAttributes']['voucher_name'] = "Voucher Name : " + obj["voucher_name"];
                                            address['customAttributes']['voucher_amount'] = "Voucher Type : " + obj["voucher_amount"];
                    
                                            // console.log(abstractTotal);
                                            // console.log(defaulRender);
                                        } else { 
                                            address['customAttributes']['cost_weight'] = 0;
                                            address['customAttributes']['cost_staircase'] = 0;
                                            address['customAttributes']['cost_location'] = 0;
                                            address['customAttributes']['voucher_name'] = "Voucher Name : -";
                                            address['customAttributes']['voucher_amount'] = "Voucher Type : 0";
                                        }
                                        // $('.cost-information .cost-to .shipping-information-content').load(self);
                                        stepNavigator.next();
                                    },
                                    fail: function (response) {
                                        console.log("failed");
                                        console.log(response);
                                        stepNavigator.next();
                                    }
                                });

                                rateRegistry.set(address.getKey(), selectShippingMethod);
                                shippingService.setShippingRates(selectShippingMethod);
                                
                            }).fail(function(response) {
                                console.log(response);
                            }
                        );

                        // stepNavigator.next();
                    }
                );
            }
        },
        /**
         * @return {Boolean}
         */
        validateShippingInformation: function () {

            var $deliveryNote = $('textarea[name="delivery_note"]');
            var note = $deliveryNote.val();

            localAction = this;

            if (note == '') {
                $deliveryNote.parent().find('.field-error').remove();
            } else {
                var limit = Number(checkoutConfig.delivery_note_limit);

                if (note.length > limit) {
                    if ($deliveryNote.parent().find('.field-error').length == 0) {
                        var errMessage = '<div class="field-error" style="margin-top: 0;"><span>' + $t('You enter exceed limit %1 characters.').replace('%1', limit) + '</span></div>';
                        $deliveryNote.parent().append(errMessage);
                    }
                    $deliveryNote.focus();
                    return false;
                } else {
                    $deliveryNote.parent().find('.field-error').remove();
                }
            }

            if (!$("input[name='delivery_date']").first().val()) {
                this.errorValidationMessage(
                    $t('The delivery date is missing. Select the delivery date and try again.')
                );
                this.focusInvalid();
                return false;
            }

            if (!$("select[name='delivery_slot']").find(":selected").val()) {
                this.errorValidationMessage(
                    $t('The timeslot is missing. Please select the timeslot and try again.')
                );
                this.focusInvalid();
                return false;
            }

            // if (!$("select[name='delivery_stairs']").find(":selected").val()) {
            //     this.errorValidationMessage(
            //         $t('The building level is missing. Please fill the building level and try again.')
            //     );
            //     this.focusInvalid();
            //     return false;
            // }

            
            // if (!$("input[name='delivery_stairs']").first().val()) {
            //     this.errorValidationMessage(
            //         $t('The building level is missing. Please fill the building level and try again.')
            //     );
            //     this.focusInvalid();
            //     return false;
            // } else {
            //     if (!$.isNumeric($("input[name='delivery_stairs']").first().val())) { 
            //         this.errorValidationMessage(
            //             $t('The building level must in number format. Please change the value and try again.')
            //         );
            //         this.focusInvalid();
            //         return false;
            //     }
            // }

            if (!quote.shippingMethod()) {
                this.errorValidationMessage(
                    $t('The delivery method is missing. Select the delivery method and try again.')
                );
                return false;
            }

            if (this.isFormInline) {
                this.source.set('params.invalid', false);

                if (
                    !quote.shippingMethod()['method_code'] ||
                    !quote.shippingMethod()['carrier_code']
                ) {
                    this.focusInvalid();
                    return false;
                }
            }
            return true;
        },

        /**
         * Trigger Shipping data Validate Event.
         */
        triggerShippingDataValidateEvent: function () {
            this.source.trigger('shippingAddress.data.validate');

            if (this.source.get('shippingAddress.custom_attributes')) {
                this.source.trigger('shippingAddress.custom_attributes.data.validate');
            }
        },

        /**
         * call API 
         */
        checkDelivery: function () { 
            console.log("checkDelivery");
            var $spanNotice = $('#message-notice');
            if (this.validateShippingInformation()) { 
                $.ajax({
                    url: '/fef_shipping/ajax/getdelivery',
                    showLoader: true,
                    type: 'POST',
                    dataType: 'json',
                    success: function (response) {
                        console.log(response);
                        if (response["success"] == true) {
                            $spanNotice.hide();
                            $('#opc-continue').show();
                        } else { 
                            $('#opc-continue').hide();
                            $spanNotice.show();
                            localAction.errorValidationMessage(
                                $t(response.message)
                            );
                            return false;
                            
                        }
                    },
                    fail: function (response) {
                        $('#opc-continue').hide();
                        console.log(response);
                    }
                });
            }
        }
            
    });
});
