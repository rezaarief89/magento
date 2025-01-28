define(
    [
        'Fef_OverrideSummaryCheckout/js/view/checkout/summary/zoku_point'
    ],
    function (Component) {
        'use strict';

        return Component.extend({

            /**
             * @override
             */
            isDisplayed: function () {
                return this.getPureValue() !== 0;
            }
        });
    }
);