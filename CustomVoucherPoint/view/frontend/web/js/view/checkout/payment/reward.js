define([
    'jquery',
    'underscore',
    'uiComponent',
    'Magento_Checkout/js/model/quote',
    'Zoku_Rewards/js/action/add-reward',
    'Zoku_Rewards/js/action/cancel-reward'
], function ($, _, Component, quote, setRewardPointAction, cancelRewardPointAction) {
    'use strict';

    return Component.extend({
        defaults: {
            template: 'Fef_CustomVoucherPoint/checkout/payment/rewards',
            isApplied: false,
            pointsUsed: 0,
            pointsLeft: 0,
            noticeMessage: '',
            minimumPointsValue: 0,
            disableElem: false
        },

        initObservable: function () {
            this._super();
            this.observe(['pointsUsed', 'pointsLeft', 'isApplied', 'noticeMessage', 'disableElem']);

            return this;
        },

        /**
         * @return {exports}
         */
        initialize: function () {
            this._super();
            this.isApplied(false);

            if (this.pointsUsed() > 0) {
                this.isApplied(true);
            }

            if (_.isUndefined(Number.parseFloat)) {
                Number.parseFloat = parseFloat;
            }

            if (this.getMinimumPointsValue() > this.pointsLeft() + Number.parseFloat(this.pointsUsed())) {
                this.disableElem(true);
            }

            return this;
        },

        /**
         * @return {*|Boolean}
         */
        isDisplayed: function () {
            return this.customerId;
        },

        /**
         * Coupon code application procedure
         */
        apply: function () {
            //call ajax to hit get point API -> create controller too
            //controller creation is to sync proseller point to magento (zoku) purpose
            //controller update the point after call api get point
            //js update the points left text
            // this.pointsLeft = 3000;
            // $("#pointsLeftText").text(this.pointsLeft);
            // console.log("after appply");
            if (this.validate()) {
                setRewardPointAction(this.pointsUsed, this.isApplied, this.pointsLeft, this.rateForCurrency, this.noticeMessage);
            }
        },

        /**
         * Cancel using coupon
         */
        cancel: function () {
            cancelRewardPointAction(this.isApplied);
            this.pointsLeft((Number.parseFloat(this.pointsLeft()) + Number.parseFloat(this.pointsUsed())).toFixed(2));
        },

        /**
         *
         * @return {*}
         */
        getRewardsCount: function () {
            return this.pointsLeft();
        },

        /**
         *
         * @return {*}
         */
        getPointsRate: function () {
            return this.pointsRate;
        },

        /**
         *
         * @return {*}
         */
        getCurrentCurrency: function () {
            return this.currentCurrencyCode;
        },

        /**
         *
         * @return {*}
         */
        getRateForCurrency: function () {
            return this.rateForCurrency;
        },

        /**
         * @return {*}
         */
        getMinimumPointsValue: function () {
            return Number.parseFloat(this.minimumPointsValue);
        },

        /**
         * @return {Boolean}
         */
        canApply: function () {
            return !(this.disableElem() || this.isApplied());
        },

        /**
         * Coupon form validation
         *
         * @returns {Boolean}
         */
        validate: function () {
            var form = '#discount-reward-form',
                valueValid = (this.pointsLeft() - this.pointsUsed() >= 0) && this.pointsUsed() > 0;

            return $(form).validation() && $(form).validation('isValid') && valueValid;
        }
    });
});
