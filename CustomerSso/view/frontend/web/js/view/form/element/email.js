/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

define([
    'jquery',
    'uiComponent',
    'ko',
    'Magento_Customer/js/model/customer',
    'Magento_Customer/js/action/check-email-availability',
    'Smartosc_Checkout/js/action/login',
    'Smartosc_Checkout/js/action/register',
    'Magento_Checkout/js/model/quote',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/full-screen-loader',
    'Smartosc_Checkout/js/model/step-navigator',
    'moment',
    'mage/validation',
    'mage/url'
], function ($, Component,
             ko,
             customer,
             checkEmailAvailability,
             loginAction,
             registerAction,
             quote,
             checkoutData,
             fullScreenLoader,
             stepNavigator,
             moment,
             validation,
             url) {
    'use strict';

    $.validator.addMethod(
        'validate-date-dob', function (v) {
            if(!v) return true;
            var regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
            if(!regex.test(v)) return false;
            var d = new Date(v.replace(regex, '$1/$2/$3'));
            return ( parseInt(RegExp.$1, 10) == (1+d.getMonth()) ) &&
                (parseInt(RegExp.$2, 10) == d.getDate()) &&
                (parseInt(RegExp.$3, 10) == d.getFullYear() );


        }, $.mage.__('Please enter a valid date.'));

    $.validator.addMethod(
        'validate-dob', function (v) {
            if (v === '') {
                return true;
            }

            return moment(v).isBefore(moment());
        }, $.mage.__('The Date of Birth should not be greater than today.'));

    var validatedEmail;

    if (!checkoutData.getValidatedEmailValue() &&
        window.checkoutConfig.validatedEmailValue
    ) {
        checkoutData.setInputFieldEmailValue(window.checkoutConfig.validatedEmailValue);
        checkoutData.setValidatedEmailValue(window.checkoutConfig.validatedEmailValue);
    }

    validatedEmail = checkoutData.getValidatedEmailValue();

    if (validatedEmail && !customer.isLoggedIn()) {
        quote.guestEmail = validatedEmail;
    }

    
    return Component.extend({
        defaults: {
            template: 'Fef_CustomerSso/form/element/email',
            email: checkoutData.getInputFieldEmailValue(),
            emailFocused: false,
            isLoading: false,
            isPasswordVisible: false,
            listens: {
                email: 'emailHasChanged',
                emailFocused: 'validateEmail'
            },
            ignoreTmpls: {
                email: true
            }
        },
        checkDelay: 2000,
        checkRequest: null,
        isEmailCheckComplete: null,
        isCustomerLoggedIn: customer.isLoggedIn,
        forgotPasswordUrl: window.checkoutConfig.forgotPasswordUrl,
        emailCheckTimeout: 0,

        /**
         * Initializes regular properties of instance.
         *
         * @returns {Object} Chainable.
         */
        initConfig: function () {
            this._super();

            this.isPasswordVisible = this.resolveInitialPasswordVisibility();

            return this;
        },

        /**
         * Initializes observable properties of instance
         *
         * @returns {Object} Chainable.
         */
        initObservable: function () {
            this._super()
                .observe(['email', 'emailFocused', 'isLoading', 'isPasswordVisible']);

            return this;
        },

        /**
         * Callback on changing email property
         */
        emailHasChanged: function () {
            var self = this;

            clearTimeout(this.emailCheckTimeout);

            if (self.validateEmail()) {
                quote.guestEmail = self.email();
                checkoutData.setValidatedEmailValue(self.email());
            }
            this.emailCheckTimeout = setTimeout(function () {
                if (self.validateEmail()) {
                    self.checkEmailAvailability();
                } else {
                    self.isPasswordVisible(false);
                }
            }, self.checkDelay);

            checkoutData.setInputFieldEmailValue(self.email());
        },

        /**
         * Check email existing.
         */
        checkEmailAvailability: function () {
            this.validateRequest();
            this.isEmailCheckComplete = $.Deferred();
            this.isLoading(true);
            this.checkRequest = checkEmailAvailability(this.isEmailCheckComplete, this.email());

            $.when(this.isEmailCheckComplete).done(function () {
                this.isPasswordVisible(false);
            }.bind(this)).fail(function () {
                this.isPasswordVisible(true);
                checkoutData.setCheckedEmailValue(this.email());
            }.bind(this)).always(function () {
                this.isLoading(false);
            }.bind(this));
        },

        /**
         * If request has been sent -> abort it.
         * ReadyStates for request aborting:
         * 1 - The request has been set up
         * 2 - The request has been sent
         * 3 - The request is in process
         */
        validateRequest: function () {
            if (this.checkRequest != null && $.inArray(this.checkRequest.readyState, [1, 2, 3])) {
                this.checkRequest.abort();
                this.checkRequest = null;
            }
        },

        /**
         * Local email validation.
         *
         * @param {Boolean} focused - input focus.
         * @returns {Boolean} - validation result.
         */
        validateEmail: function (focused) {
            var loginFormSelector = 'form[data-role=email-with-possible-login]',
                usernameSelector = loginFormSelector + ' input[name=username]',
                loginForm = $(loginFormSelector),
                validator,
                valid;

            loginForm.validation();

            if (focused === false && !!this.email()) {
                valid = !!$(usernameSelector).valid();

                if (valid) {
                    $(usernameSelector).removeAttr('aria-invalid aria-describedby');
                }

                return valid;
            }

            validator = loginForm.validate();

            return validator.check(usernameSelector);
        },

        /**
         * Log in form submitting callback.
         *
         * @param {HTMLElement} loginForm - form element.
         */
        login: function (loginForm) {
            var loginData = {},
                formDataArray = $(loginForm).serializeArray();

            formDataArray.forEach(function (entry) {
                loginData[entry.name] = entry.value;
            });

            if (this.isPasswordVisible() && $(loginForm).validation() && $(loginForm).validation('isValid')) {
                var buttonText = $("#checkout-btn-login").text();
                console.log(buttonText);
                if($("#customer-email").val()==""){
                    alert("Please fill the email first");
                }else{
                    if(buttonText=="Sign In"){
                        this.validateEmailLogin(0);
                    }else{
                        if($("#checkout-login-otp").val()==""){
                            alert("Please fill OTP to submit login");
                        } else {
                            this.validateEmailLogin($("#checkout-login-otp").val());
                            $('#checkout-validation-info').hide();
                        }
                    }
                }
            }
        },

        validateEmailLogin: function(otpValue) {
            
            $.ajax({
                url: '/sso/customer/syncemail',
                cache: false,
                showLoader: true,
                type: 'POST',
                data: {
                    email: $("#customer-email").val(),
                    otp: otpValue
                },
                success: function (response) {
                    if(response.success==true){
                        $('#checkout-login-otp').show();
                        $('#checkout-login-resend').show();
                        if($("#checkout-btn-login").text()=="Submit"){
                            $("#checkout-btn-login").html("Sign In");
                            $('#checkout-login-otp').hide();
                            $('#checkout-login-resend').hide();
                            window.location.href = "/checkout";
                        }else{
                            $("#checkout-btn-login").html("Submit");
                        }
                        $('#checkout-validation-info').text(response.message);
                        $('#checkout-validation-info').show();
                        $('#checkout-validation-error').hide();
                    } else {
                        $('#checkout-validation-error').show();
                        $('#checkout-validation-error').text(response.message);
                        $('#checkout-login-otp').hide();   
                        $('#checkout-login-resend').hide();
                        $("#checkout-btn-login").html("Sign In");
                        $('#checkout-validation-info').hide();
                    }
                    
                },
                fail: function (response) {
                    console.log("failed");
                    console.log(response);
                }
            });
        },

        /**
         * Resolves an initial state of a login form.
         *
         * @returns {Boolean} - initial visibility state.
         */
        resolveInitialPasswordVisibility: function () {
            if (checkoutData.getInputFieldEmailValue() !== '' && checkoutData.getCheckedEmailValue() === '') {
                return true;
            }

            if (checkoutData.getInputFieldEmailValue() !== '') {
                return checkoutData.getInputFieldEmailValue() === checkoutData.getCheckedEmailValue();
            }

            return false;
        },

        resendOtp: function () {

            if($("#customer-email").val()==""){
                alert("Please fill the email first");
            }else{
                $.ajax({
                    url: '/sso/customer/resendotp',
                    cache: false,
                    showLoader: true,
                    type: 'POST',
                    data: {
                        "email" : $("#customer-email").val(),
                    },
                    success: function (response) {
                        console.log(response);
                        if(response.success==true){   
                            $('#checkout-validation-info').text(response.message);
                            $('#checkout-validation-info').show();
                            $('#checkout-validation-error').hide();
                            $('#checkout-login-otp').show();
                            $('#checkout-login-resend').show();
                            $("#checkout-btn-login").html("Submit");
                        } else {
                            $('#checkout-validation-error').show();
                            $('#checkout-validation-error').text(response.message);
                            $('#checkout-validation-info').hide();
                            $('#checkout-login-otp').hide();
                            $('#checkout-login-resend').hide();
                            $("#checkout-btn-login").html("Sign In");
                        }
                    },
                    fail: function (response) {
                        console.log("failed");
                        console.log(response);
                    }
                });
            }
        },

        
        resendOtpRegister: function () {

            if($("#email").val()==""){
                alert("Please fill the email first");
            }else{
                $.ajax({
                    url: '/sso/customer/resendotp',
                    cache: false,
                    showLoader: true,
                    type: 'POST',
                    data: {
                        "email" : $("#email").val(),
                    },
                    success: function (response) {
                        console.log(response);
                        if(response.success==true){   
                            $('#checkout-register-validation-info').text(response.message);
                            $('#checkout-register-validation-info').show();
                            $('#checkout-register-validation-error').hide();
                            $('#checkout-register-otp').show();
                            $("#checkout-btn-register").html("Submit");
                        } else {
                            $('#checkout-register-validation-error').show();
                            $('#checkout--validation-error').text(response.message);
                            $('#checkout-register-validation-info').hide();
                            $('#register-otp').hide();
                            $("#checkout-btn-register").html("Create an account");
                        }
                    },
                    fail: function (response) {
                        console.log("failed");
                        console.log(response);
                    }
                });
            }
        },
        
        /**
         * Registration form submitting callback.
         *
         * @param {HTMLElement} registerForm - form element.
         */
        registerNewUser: function (registerForm) {
            var registerData = {},
                formDataArray = $(registerForm).serializeArray(),
                redirectUrl = url.build('smart_checkout/customer_ajax/register');

            let country_phone_code = $("#contact_number").val();
            let telephone = $("#contact_number_1").val();
            let city = $("#country option:selected").text();
            formDataArray.forEach(function (entry) {
                registerData[entry.name] = entry.value;
                if (entry.name === 'country_phone_code'){
                    registerData[entry.name] = country_phone_code;
                }
                if (entry.name === 'telephone'){
                    registerData[entry.name] = telephone;
                }
                if (entry.name === 'city'){
                    registerData[entry.name] = city;
                }
            });


            if ($(registerForm).validation() && $(registerForm).validation('isValid')) {

                var buttonText = $("#checkout-btn-register").text();
                if(buttonText=="Create an account"){
                    this.registerEmail(registerData,0);
                }else{
                    if($("#checkout-register-otp").val()==""){
                        alert("Please fill OTP to submit register");
                    } else {
                        this.registerEmail(registerData,$("#checkout-register-otp").val());
                        $('#checkout-register-validation-info').hide();
                    }
                }
                
                // fullScreenLoader.startLoader();
                // registerAction(registerData, redirectUrl).always(function () {
                //     fullScreenLoader.stopLoader();
                // });
            }

        },

        registerEmail: function(registerData,otpValue) {
            var otpField = $('#checkout-register-otp');
            console.log(registerData);
            // var formData = $form.serialize();
            
            $.ajax({
                url: '/sso/customer/registeremail',
                cache: false,
                showLoader: true,
                type: 'POST',
                data: registerData,
                success: function (response) {
                    console.log(response);
                    if(response.success==true){                        
                        // $("#fieldset-create-info").hide();

                        if($("#checkout-btn-register").text()=="Submit"){
                            console.log("after submit");
                            $("#checkout-btn-register").html("Create an account");
                            otpField.hide();
                            $('#checkout-register-resend').hide();
                            window.location.href = "/checkout";
                        }else{
                            console.log("after create account");
                            $("#checkout-btn-register").html("Submit");
                        }
                        $('#checkout-register-validation-info').text(response.message);
                        $('#checkout-register-validation-info').show();
                        $('#checkout-register-validation-error').hide();
                        otpField.show();
                        $('#checkout-register-resend').show();
                    } else {
                        if($("#checkout-btn-register").text()!="Submit"){
                            otpField.hide();
                            $('#checkout-register-resend').hide();
                        }
                        $('#checkout-register-validation-error').show();
                        $('#checkout-register-validation-error').text(response.message);
                        $('#checkout-register-validation-info').hide();
                    }
                    $('.loader').hide();
                    $('.loading-mask').hide();
                },
                fail: function (response) {
                    console.log("failed");
                    console.log(response);
                }
            });
        },



        showDay: function () {
            let html = "";
            html += '<option value="">DD</option>';

            for (var i = 1; i <= 31; i++) {
                html += '<option value="' + i + '">' + i + '</option>';
            }
            return html;
        },

        showMonth: function () {
            let html = "";
            html += '<option value="">MM</option>';

            for (var i = 1; i <= 12; i++) {
                html += '<option value="' + i + '">' + i + '</option>';
            }
            return html;
        },

        showYear: function () {
            let html = "";
            html += '<option value="">YYYY</option>';
            const d = new Date();
            let year = d.getFullYear();
            for (var i = 1950; i <= year; i++) {
                html += '<option value="' + i + '">' + i + '</option>';
            }
            return html;
        },

        fortmatDateDob: function (){
            let month = $("#dob_mm").val();
            let day = $("#dob_dd").val();
            let year = $("#dob_yy").val();
            if( month != "" || day != "" || year != "") {
                $('input[id=dob]').val(month + '/' + day + '/' + year);

            }

            if( month != "" && day != "" && year != "") {
                let dayConvert = day;
                if (dayConvert < 10) {
                    dayConvert = '0' + day;
                }
                let monthConvert = month;
                if (monthConvert < 10) {
                    monthConvert = '0' + monthConvert;
                }
                $('#dob-validate').val(monthConvert + '/' + dayConvert + '/' + year);
            }
            else {
                $('#dob-validate').val("");
            }
        },

        getCountryCode: function () {
          return window.checkoutConfig.contact_number_element_html;
        },

        getDobTooltip: function () {
            return window.checkoutConfig.dob_tooltip_label;
        },

        getNumberTooltip: function () {
            return window.checkoutConfig.contact_number_tooltip_label;
        },

        getTermUrl: function () {
            return window.checkoutConfig.term_url;
        },
        getPrivacyUrl: function () {
            return window.checkoutConfig.privacy_url;
        },
        getCountryList: function () {
            return window.checkoutConfig.country_element_html + '<label class="label" for="country"><span>' + $.mage.__('Country') + '</span></label>';
        },
        setCountryDefault: function () {
            var countryDefault = window.checkoutConfig.defaultCountryId;
            if( $("#customer_account_create").find('select[name="country_id"]').length ) {
                $("#customer_account_create").find('select[name="country_id"]').val(countryDefault);
            }
        },

        /**
         * @returns void
         */
        navigateToNextStep: function () {
            stepNavigator.next();
        }
    });
});
