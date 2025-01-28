/**
 * @api
 */
define([
    'jquery',
    'ko',
    'mage/url',
    'moment',
    'mageUtils',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'moment-timezone-with-data'
], function ($, ko, urlHelper, moment, utils, registry, Abstract) {
    'use strict';



    return Abstract.extend({
        defaults: {
            options: {},

            dataScope: "delivery-date.delivery_date",
            storeTimeZone: 'UTC',

            validationParams: {
                dateFormat: '${ $.outputDateFormat }'
            },

            /**
             * Format of date that comes from the
             * server (ICU Date Format).
             *
             * Used only in date picker mode
             * (this.options.showsTime == false).
             *
             * @type {String}
             */
            inputDateFormat: 'y-MM-dd',

            /**
             * Format of date that should be sent to the
             * server (ICU Date Format).
             *
             * Used only in date picker mode
             * (this.options.showsTime == false).
             *
             * @type {String}
             */
            outputDateFormat: 'MM/dd/y',

            /**
             * Date/time format that is used to display date in
             * the input field.
             *
             * @type {String}
             */
            pickerDateTimeFormat: '',

            pickerDefaultDateFormat: 'MM/dd/y', // ICU Date Format
            pickerDefaultTimeFormat: 'h:mm a', // ICU Time Format

            elementTmpl: 'Smartosc_Checkout/form/element/smart_date',

            /**
             * Format needed by moment timezone for conversion
             */
            timezoneFormat: 'YYYY-MM-DD HH:mm',

            listens: {
                'value': 'onValueChange',
                'shiftedValue': 'onShiftedValueChange'
            },

            /**
             * Date/time value shifted to corresponding timezone
             * according to this.storeTimeZone property. This value
             * will be sent to the server.
             *
             * @type {String}
             */
            shiftedValue: ''
        },
        /**
         * Entry point to the initialization of constructor's instance.
         *
         * @param {Object} [options={}]
         * @returns {Class} Chainable.
         */
        initialize: function (options) {

            this._super();

            return this;
        },
        /** This callback will overriden jquery datepicker ui  */
        cb: function(node) {

            /**
             *
             * @param {array} config
             * @param {int} postalcode
             * @return {bool}
             */
            function checkIfBillingPostalcodeValid(config, postalcode) {

                for (var elem of config) {
                    if (postalcode == elem.trim() )
                        return true;
                }

                return false;
            }

            $.extend(
                $.datepicker,
                {
                    _generateHTML: //_showDatepicker
                        function(inst) {
                            var maxDraw, prevText, prev, nextText, next, currentText, gotoDate,
                                controls, buttonPanel, firstDay, showWeek, dayNames, dayNamesMin,
                                monthNames, monthNamesShort, beforeShowDay, showOtherMonths,
                                selectOtherMonths, defaultDate, html, dow, row, group, col, selectedDate,
                                cornerClass, calender, thead, day, daysInMonth, leadDays, curRows, numRows,
                                printDate, dRow, tbody, daySettings, otherMonth, unselectable,
                                tempDate = new Date(),
                                today = this._daylightSavingAdjust(
                                    new Date(tempDate.getFullYear(), tempDate.getMonth(), tempDate.getDate())), // clear time
                                isRTL = this._get(inst, "isRTL"),
                                showButtonPanel = this._get(inst, "showButtonPanel"),
                                hideIfNoPrevNext = this._get(inst, "hideIfNoPrevNext"),
                                navigationAsDateFormat = this._get(inst, "navigationAsDateFormat"),
                                numMonths = this._getNumberOfMonths(inst),
                                showCurrentAtPos = this._get(inst, "showCurrentAtPos"),
                                stepMonths = this._get(inst, "stepMonths"),
                                isMultiMonth = (numMonths[0] !== 1 || numMonths[1] !== 1),
                                currentDate = this._daylightSavingAdjust((!inst.currentDay ? new Date(9999, 9, 9) :
                                    new Date(inst.currentYear, inst.currentMonth, inst.currentDay))),
                                minDate = this._getMinMaxDate(inst, "min"),
                                maxDate = this._getMinMaxDate(inst, "max"),
                                drawMonth = inst.drawMonth - showCurrentAtPos,
                                drawYear = inst.drawYear;

                            if (drawMonth < 0) {
                                drawMonth += 12;
                                drawYear--;
                            }
                            if (maxDate) {
                                maxDraw = this._daylightSavingAdjust(new Date(maxDate.getFullYear(),
                                    maxDate.getMonth() - (numMonths[0] * numMonths[1]) + 1, maxDate.getDate()));
                                maxDraw = (minDate && maxDraw < minDate ? minDate : maxDraw);
                                while (this._daylightSavingAdjust(new Date(drawYear, drawMonth, 1)) > maxDraw) {
                                    drawMonth--;
                                    if (drawMonth < 0) {
                                        drawMonth = 11;
                                        drawYear--;
                                    }
                                }
                            }
                            inst.drawMonth = drawMonth;
                            inst.drawYear = drawYear;

                            prevText = this._get(inst, "prevText");
                            prevText = (!navigationAsDateFormat ? prevText : this.formatDate(prevText,
                                this._daylightSavingAdjust(new Date(drawYear, drawMonth - stepMonths, 1)),
                                this._getFormatConfig(inst)));

                            prev = (this._canAdjustMonth(inst, -1, drawYear, drawMonth) ?
                                "<a class='ui-datepicker-prev ui-corner-all' data-handler='prev' data-event='click'" +
                                " title='" + prevText + "'><span class='ui-icon ui-icon-circle-triangle-" + (isRTL ? "e" : "w") + "'>" + prevText + "</span></a>" :
                                (hideIfNoPrevNext ? "" : "<a class='ui-datepicker-prev ui-corner-all ui-state-disabled' title='" + prevText + "'><span class='ui-icon ui-icon-circle-triangle-" + (isRTL ? "e" : "w") + "'>" + prevText + "</span></a>"));

                            nextText = this._get(inst, "nextText");
                            nextText = (!navigationAsDateFormat ? nextText : this.formatDate(nextText,
                                this._daylightSavingAdjust(new Date(drawYear, drawMonth + stepMonths, 1)),
                                this._getFormatConfig(inst)));

                            next = (this._canAdjustMonth(inst, +1, drawYear, drawMonth) ?
                                "<a class='ui-datepicker-next ui-corner-all' data-handler='next' data-event='click'" +
                                " title='" + nextText + "'><span class='ui-icon ui-icon-circle-triangle-" + (isRTL ? "w" : "e") + "'>" + nextText + "</span></a>" :
                                (hideIfNoPrevNext ? "" : "<a class='ui-datepicker-next ui-corner-all ui-state-disabled' title='" + nextText + "'><span class='ui-icon ui-icon-circle-triangle-" + (isRTL ? "w" : "e") + "'>" + nextText + "</span></a>"));

                            currentText = this._get(inst, "currentText");
                            gotoDate = (this._get(inst, "gotoCurrent") && inst.currentDay ? currentDate : today);
                            currentText = (!navigationAsDateFormat ? currentText :
                                this.formatDate(currentText, gotoDate, this._getFormatConfig(inst)));

                            controls = (!inst.inline ? "<button type='button' class='ui-datepicker-close ui-state-default ui-priority-primary ui-corner-all' data-handler='hide' data-event='click'>" +
                                this._get(inst, "closeText") + "</button>" : "");

                            buttonPanel = (showButtonPanel) ? "<div class='ui-datepicker-buttonpane ui-widget-content'>" + (isRTL ? controls : "") +
                                (this._isInRange(inst, gotoDate) ? "<button type='button' class='ui-datepicker-current ui-state-default ui-priority-secondary ui-corner-all' data-handler='today' data-event='click'" +
                                    ">" + currentText + "</button>" : "") + (isRTL ? "" : controls) + "</div>" : "";

                            firstDay = parseInt(this._get(inst, "firstDay"), 10);
                            firstDay = (isNaN(firstDay) ? 0 : firstDay);

                            showWeek = this._get(inst, "showWeek");
                            dayNames = this._get(inst, "dayNames");
                            dayNamesMin = this._get(inst, "dayNamesMin");
                            monthNames = this._get(inst, "monthNames");
                            monthNamesShort = this._get(inst, "monthNamesShort");

                            var billingPostalcode = requirejs('uiRegistry').get("checkout.steps.shipping-step.shippingAddress.shipping-address-fieldset.postcode").value();
                            if (!billingPostalcode) {
                                var billingPostalcode = requirejs('uiRegistry').get("checkout.steps.shipping-step.shippingAddress.billingAddress.address-fieldset.postcode").value();
                            }

                            var unavailableDates = window.checkoutConfig.special_disable_date;
                            var unavailableDatesTimeString = unavailableDates.map(function ($item) {
                                var [day, month, year] = $item.split('-');
                                var validDate = new Date(year, month - 1 , day);
                                return Date.parse(validDate.getFullYear() + "-" + (validDate.getMonth()+1) + "-" +validDate.getDate());
                            });
                            function unavailable(date) {
                                var dmy = Date.parse(date.getFullYear() + "-" + (date.getMonth()+1) + "-" +date.getDate());
                                var farthestShippingDate = new Date(window.checkoutConfig.smartosc_preorder_delivery_date.date);
                                if (date.getTime() < farthestShippingDate.getTime()){
                                    return [false, "", ""];
                                } else if ($.inArray(dmy, unavailableDatesTimeString) < 0) {
                                    return [true,"",""];
                                } else {
                                    return [false,"",""];
                                }
                            }

                            beforeShowDay = this._get(inst, "beforeShowDay");

                            var configPostalcodeAllowArray = [];
                            $.ajax({
                                url:  urlHelper.build('smart_checkout/customer_ajax/getChristmasPostalCode'),
                                data: {},
                                type: "POST",
                                dataType: 'json',
                                async: false
                            }).done(function (data) {
                                if (!data.errors) {
                                    configPostalcodeAllowArray = data.data;
                                }
                            });

                            if (!billingPostalcode || !configPostalcodeAllowArray || !checkIfBillingPostalcodeValid(configPostalcodeAllowArray, billingPostalcode)) {
                                beforeShowDay = unavailable;
                            }

                            showOtherMonths = this._get(inst, "showOtherMonths");
                            selectOtherMonths = this._get(inst, "selectOtherMonths");
                            defaultDate = this._getDefaultDate(inst);
                            html = "";
                            dow;
                            for (row = 0; row < numMonths[0]; row++) {
                                group = "";
                                this.maxRows = 4;
                                for (col = 0; col < numMonths[1]; col++) {
                                    selectedDate = this._daylightSavingAdjust(new Date(drawYear, drawMonth, inst.selectedDay));
                                    cornerClass = " ui-corner-all";
                                    calender = "";
                                    if (isMultiMonth) {
                                        calender += "<div class='ui-datepicker-group";
                                        if (numMonths[1] > 1) {
                                            switch (col) {
                                                case 0:
                                                    calender += " ui-datepicker-group-first";
                                                    cornerClass = " ui-corner-" + (isRTL ? "right" : "left");
                                                    break;
                                                case numMonths[1] - 1:
                                                    calender += " ui-datepicker-group-last";
                                                    cornerClass = " ui-corner-" + (isRTL ? "left" : "right");
                                                    break;
                                                default:
                                                    calender += " ui-datepicker-group-middle";
                                                    cornerClass = "";
                                                    break;
                                            }
                                        }
                                        calender += "'>";
                                    }
                                    calender += "<div class='ui-datepicker-header ui-widget-header ui-helper-clearfix" + cornerClass + "'>" +
                                        (/all|left/.test(cornerClass) && row === 0 ? (isRTL ? next : prev) : "") +
                                        (/all|right/.test(cornerClass) && row === 0 ? (isRTL ? prev : next) : "") +
                                        this._generateMonthYearHeader(inst, drawMonth, drawYear, minDate, maxDate,
                                            row > 0 || col > 0, monthNames, monthNamesShort) + // draw month headers
                                        "</div><table class='ui-datepicker-calendar'><thead>" +
                                        "<tr>";
                                    thead = (showWeek ? "<th class='ui-datepicker-week-col'>" + this._get(inst, "weekHeader") + "</th>" : "");
                                    for (dow = 0; dow < 7; dow++) { // days of the week
                                        day = (dow + firstDay) % 7;
                                        thead += "<th" + ((dow + firstDay + 6) % 7 >= 5 ? " class='ui-datepicker-week-end'" : "") + ">" +
                                            "<span title='" + dayNames[day] + "'>" + dayNamesMin[day] + "</span></th>";
                                    }
                                    calender += thead + "</tr></thead><tbody>";
                                    daysInMonth = this._getDaysInMonth(drawYear, drawMonth);
                                    if (drawYear === inst.selectedYear && drawMonth === inst.selectedMonth) {
                                        inst.selectedDay = Math.min(inst.selectedDay, daysInMonth);
                                    }
                                    leadDays = (this._getFirstDayOfMonth(drawYear, drawMonth) - firstDay + 7) % 7;
                                    curRows = Math.ceil((leadDays + daysInMonth) / 7); // calculate the number of rows to generate
                                    numRows = (isMultiMonth ? this.maxRows > curRows ? this.maxRows : curRows : curRows); //If multiple months, use the higher number of rows (see #7043)
                                    this.maxRows = numRows;
                                    printDate = this._daylightSavingAdjust(new Date(drawYear, drawMonth, 1 - leadDays));
                                    for (dRow = 0; dRow < numRows; dRow++) { // create date picker rows
                                        calender += "<tr>";
                                        tbody = (!showWeek ? "" : "<td class='ui-datepicker-week-col'>" +
                                            this._get(inst, "calculateWeek")(printDate) + "</td>");
                                        for (dow = 0; dow < 7; dow++) { // create date picker days
                                            daySettings = (beforeShowDay ?
                                                beforeShowDay.apply((inst.input ? inst.input[0] : null), [printDate]) : [true, ""]);
                                            otherMonth = (printDate.getMonth() !== drawMonth);
                                            unselectable = (otherMonth && !selectOtherMonths) || !daySettings[0] ||
                                                (minDate && printDate < minDate) || (maxDate && printDate > maxDate);
                                            tbody += "<td class='" +
                                                ((dow + firstDay + 6) % 7 >= 5 ? " ui-datepicker-week-end" : "") + // highlight weekends
                                                (otherMonth ? " ui-datepicker-other-month" : "") + // highlight days from other months
                                                ((printDate.getTime() === selectedDate.getTime() && drawMonth === inst.selectedMonth && inst._keyEvent) || // user pressed key
                                                (defaultDate.getTime() === printDate.getTime() && defaultDate.getTime() === selectedDate.getTime()) ?
                                                    // or defaultDate is current printedDate and defaultDate is selectedDate
                                                    " " + this._dayOverClass : "") + // highlight selected day
                                                (unselectable ? " " + this._unselectableClass + " ui-state-disabled" : "") +  // highlight unselectable days
                                                (otherMonth && !showOtherMonths ? "" : " " + daySettings[1] + // highlight custom dates
                                                    (printDate.getTime() === currentDate.getTime() ? " " + this._currentClass : "") + // highlight selected day
                                                    (printDate.getTime() === today.getTime() ? " ui-datepicker-today" : "")) + "'" + // highlight today (if different)
                                                ((!otherMonth || showOtherMonths) && daySettings[2] ? " title='" + daySettings[2].replace(/'/g, "&#39;") + "'" : "") + // cell title
                                                (unselectable ? "" : " data-handler='selectDay' data-event='click' data-month='" + printDate.getMonth() + "' data-year='" + printDate.getFullYear() + "'") + ">" + // actions
                                                (otherMonth && !showOtherMonths ? "&#xa0;" : // display for other months
                                                    (unselectable ? "<span class='ui-state-default'>" + printDate.getDate() + "</span>" : "<a class='ui-state-default" +
                                                        (printDate.getTime() === today.getTime() ? " ui-state-highlight" : "") +
                                                        (printDate.getTime() === currentDate.getTime() ? " ui-state-active" : "") + // highlight selected day
                                                        (otherMonth ? " ui-priority-secondary" : "") + // distinguish dates from other months
                                                        "' href='#'>" + printDate.getDate() + "</a>")) + "</td>"; // display selectable date
                                            printDate.setDate(printDate.getDate() + 1);
                                            printDate = this._daylightSavingAdjust(printDate);
                                        }
                                        calender += tbody + "</tr>";
                                    }
                                    drawMonth++;
                                    if (drawMonth > 11) {
                                        drawMonth = 0;
                                        drawYear++;
                                    }
                                    calender += "</tbody></table>" + (isMultiMonth ? "</div>" +
                                        ((numMonths[0] > 0 && col === numMonths[1] - 1) ? "<div class='ui-datepicker-row-break'></div>" : "") : "");
                                    group += calender;
                                }
                                html += group;
                            }
                            html += buttonPanel;
                            inst._keyEvent = false;
                            return html;
                        }
                }
            );
        },

        /**
         * Initializes regular properties of instance.
         *
         * @returns {Object} Chainable.
         */
        initConfig: function () {

            function noWeekendsOrHolidays(date) {
                //Wednesday, Friday and Sunday;
                var day = date.getDay();

                var unavailableDates = window.checkoutConfig.special_disable_date;
                var dmy = date.getDate() + "-" + (date.getMonth()+1) + "-" + date.getFullYear();
                var farthestShippingDate = new Date(window.checkoutConfig.smartosc_preorder_delivery_date.date);
                if (date.getTime() < farthestShippingDate.getTime()){
                    return [false, "", ""];
                } else if (unavailableDates.includes(dmy)) {
                    return [false, "", ""];
                }else {
                    return [(day === 3 || day ===0 || day === 5) , "", ""];
                }
            }

            this._super();
            var configDeliveryDate = window.checkoutConfig.delivery_date_rule_setting;
            var billingPostalcode = $('input[name="postcode"]').first().val();

            this.options.beforeShowDay = noWeekendsOrHolidays;

            if (!this.options.dateFormat) {
                this.options.dateFormat = this.pickerDefaultDateFormat;
            }

            if (!this.options.timeFormat) {
                this.options.timeFormat = this.pickerDefaultTimeFormat;
            }

            this.prepareDateTimeFormats();

            return this;
        },

        /**
         * @inheritdoc
         */
        initObservable: function () {
            return this._super().observe(['shiftedValue']);
        },

        getPreviousDay : function (date = new Date(), minusDay = 1) {
            const previous = new Date(date.getTime());
            previous.setDate(date.getDate() - minusDay);
          
            return previous;
        },

        /**
         * Prepares and sets date/time value that will be displayed
         * in the input field.
         *
         * @param {String} value
         */
        onValueChange: function (value) {
            var shiftedValue, paramsValue, shiftedValue2;

            if (value) {
                if (this.options.showsTime) {
                    shiftedValue = moment.tz(value, 'UTC').tz(this.storeTimeZone);
                } else {
                    shiftedValue = moment(value, this.outputDateFormat);
                }

                if (!shiftedValue.isValid()) {
                    shiftedValue = moment(value, this.inputDateFormat);
                }
                shiftedValue = shiftedValue.format(this.pickerDateTimeFormat);
            } else {
                shiftedValue = '';
            }

            if (shiftedValue !== this.shiftedValue()) {
                this.shiftedValue(shiftedValue);
            }

            var $deliverySlotEL = $("[name='delivery_slot']");
            $deliverySlotEL.empty();

            // var paramsDate = shiftedValue;
            var paramsDate = new Date(shiftedValue);
            
            var blockDays = window.checkoutConfig.block_days;
            blockDays = parseInt(blockDays);
            
            var fixedParamsDate = this.getPreviousDay(new Date(paramsDate), blockDays);

            if (this.options.showsTime) {
                shiftedValue2 = moment.tz(fixedParamsDate, 'UTC').tz(this.storeTimeZone);
            } else {
                shiftedValue2 = moment(fixedParamsDate, this.outputDateFormat);
            }

            if (!shiftedValue2.isValid()) {
                shiftedValue2 = moment(fixedParamsDate, this.inputDateFormat);
            }
            shiftedValue2 = shiftedValue2.format(this.pickerDateTimeFormat);
            
            $.ajax({
                url: '/fef_shipping/ajax/gettimeslot',
                showLoader: true,
                type: 'POST',
                data: {
                    // delivery_date: shiftedValue
                    delivery_date: shiftedValue2
                },
                dataType: 'json',
                success: function (response) {
                    if (response["success"] == true) {
                        var dataTimeslot = $.parseJSON(response["dataTimeslot"]);
                        
                        $.each(dataTimeslot, function(key,val) {
                            $deliverySlotEL.append($("<option></option>").attr("value", val).text(val));
                        });
                    } else {
                        $deliverySlotEL.append($("<option></option>").attr("value", "").text("No available time slot for selected date"));
                    }
                },
                fail: function (response) {
                    console.log("failed");
                    console.log(response);
                    $deliverySlotEL.append($("<option></option>").attr("value", "").text("No available time slot for selected date"));
                }
            });
        },

        /**
         * Prepares and sets date/time value that will be sent
         * to the server.
         *
         * @param {String} shiftedValue
         */
        onShiftedValueChange: function (shiftedValue) {
            var value,
                formattedValue,
                momentValue;

            if (shiftedValue) {
                momentValue = moment(shiftedValue, this.pickerDateTimeFormat);

                if (this.options.showsTime) {
                    formattedValue = moment(momentValue).format(this.timezoneFormat);
                    value = moment.tz(formattedValue, this.storeTimeZone).tz('UTC').toISOString();
                } else {
                    value = momentValue.format(this.outputDateFormat);
                }
            } else {
                value = '';
            }

            if (value !== this.value()) {
                this.value(value);
            }
        },

        /**
         * Prepares and converts all date/time formats to be compatible
         * with moment.js library.
         */
        prepareDateTimeFormats: function () {
            this.pickerDateTimeFormat = this.options.dateFormat;

            if (this.options.showsTime) {
                this.pickerDateTimeFormat += ' ' + this.options.timeFormat;
            }

            this.pickerDateTimeFormat = utils.convertToMomentFormat(this.pickerDateTimeFormat);

            if (this.options.dateFormat) {
                this.outputDateFormat = this.options.dateFormat;
            }

            this.inputDateFormat = utils.convertToMomentFormat(this.inputDateFormat);
            this.outputDateFormat = utils.convertToMomentFormat(this.outputDateFormat);

            this.validationParams.dateFormat = this.outputDateFormat;
        }
    });
});
