/**
 * WooCommerce Extra Product Options - Frontend JavaScript
 *
 * Handles dynamic price updates when variations are selected
 */

(function($) {
    'use strict';

    // Main WCEPO frontend object
    var WCEPO_Frontend = {
        /**
         * Initialize
         */
        init: function() {
            if (typeof wcepo_params === 'undefined') {
                return;
            }

            this.bindEvents();
            this.initializePriceDisplay();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Listen for variation changes
            $(document).on('found_variation', '.variations_form', function(event, variation) {
                self.onVariationFound(variation);
            });

            // Listen for variation reset
            $(document).on('reset_data', '.variations_form', function() {
                self.onVariationReset();
            });

            // Listen for hide_variation event
            $(document).on('hide_variation', '.variations_form', function() {
                self.onVariationReset();
            });
        },

        /**
         * Initialize price display on page load
         */
        initializePriceDisplay: function() {
            if (!wcepo_params.is_variable) {
                return;
            }

            // Set initial "From" price display
            this.showFromPrice();
        },

        /**
         * Show "From" price for variable products
         */
        showFromPrice: function() {
            var $priceElement = this.getPriceElement();

            if (!$priceElement.length) {
                return;
            }

            var priceHtml = '';

            if (wcepo_params.show_from_text === 'yes') {
                priceHtml = '<span class="wcepo-from-text">' + wcepo_params.from_text + '</span> ';
            }

            priceHtml += wcepo_params.min_price_html;

            $priceElement.html('<span class="wcepo-price-wrapper">' + priceHtml + '</span>');

            // Hide handling fee info when showing "From" price
            this.hideHandlingFeeInfo();
        },

        /**
         * Handle variation found
         *
         * @param {Object} variation Variation data
         */
        onVariationFound: function(variation) {
            var $priceElement = this.getPriceElement();

            if (!$priceElement.length) {
                return;
            }

            // Check if we have custom price data from our plugin
            if (variation.wcepo_total_price_html && wcepo_params.include_handling === 'yes') {
                $priceElement.html('<span class="wcepo-price-wrapper">' + variation.wcepo_total_price_html + '</span>');
            } else if (variation.price_html) {
                $priceElement.html(variation.price_html);
            }

            // Update handling fee display if showing separately
            if (wcepo_params.show_fee_separate === 'yes' && variation.wcepo_handling_fee > 0) {
                this.showHandlingFeeInfo(variation.wcepo_handling_fee);
            } else {
                this.hideHandlingFeeInfo();
            }
        },

        /**
         * Handle variation reset
         */
        onVariationReset: function() {
            if (!wcepo_params.is_variable) {
                return;
            }

            // Restore "From" price display
            this.showFromPrice();
        },

        /**
         * Get the price element on the page
         *
         * @return {jQuery} Price element
         */
        getPriceElement: function() {
            // Try different common selectors for price elements
            var selectors = [
                '.single_variation_wrap .woocommerce-variation-price',
                '.product .summary .price',
                '.product-info .price',
                '.woocommerce-variation-price .price'
            ];

            // For variable products before variation is selected
            var $element = $('.summary .price').first();

            // If we have a variation price element, use it when variation is selected
            var $variationPrice = $('.single_variation_wrap .woocommerce-variation-price .price');
            if ($variationPrice.length && $variationPrice.is(':visible')) {
                return $variationPrice;
            }

            return $element;
        },

        /**
         * Show handling fee info
         *
         * @param {Number} fee Handling fee amount
         */
        showHandlingFeeInfo: function(fee) {
            var $feeInfo = $('.wcepo-handling-fee-info');

            if (!$feeInfo.length) {
                return;
            }

            var feeHtml = this.formatPrice(fee);
            $feeInfo.find('.wcepo-handling-fee-amount').html(feeHtml);
            $feeInfo.show();
        },

        /**
         * Hide handling fee info
         */
        hideHandlingFeeInfo: function() {
            $('.wcepo-handling-fee-info').hide();
        },

        /**
         * Format price according to WooCommerce settings
         *
         * @param {Number} price Price to format
         * @return {String} Formatted price HTML
         */
        formatPrice: function(price) {
            var formatted = this.numberFormat(
                price,
                wcepo_params.decimals,
                wcepo_params.decimal_sep,
                wcepo_params.thousand_sep
            );

            var symbol = wcepo_params.currency_symbol;
            var position = wcepo_params.currency_position;

            switch (position) {
                case 'left':
                    return '<span class="woocommerce-Price-amount amount">' + symbol + formatted + '</span>';
                case 'right':
                    return '<span class="woocommerce-Price-amount amount">' + formatted + symbol + '</span>';
                case 'left_space':
                    return '<span class="woocommerce-Price-amount amount">' + symbol + '&nbsp;' + formatted + '</span>';
                case 'right_space':
                    return '<span class="woocommerce-Price-amount amount">' + formatted + '&nbsp;' + symbol + '</span>';
                default:
                    return '<span class="woocommerce-Price-amount amount">' + symbol + formatted + '</span>';
            }
        },

        /**
         * Format number with thousand separators and decimal places
         *
         * @param {Number} number    Number to format
         * @param {Number} decimals  Number of decimal places
         * @param {String} decSep    Decimal separator
         * @param {String} thousSep  Thousand separator
         * @return {String} Formatted number
         */
        numberFormat: function(number, decimals, decSep, thousSep) {
            number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
            var n = !isFinite(+number) ? 0 : +number,
                prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
                sep = (typeof thousSep === 'undefined') ? ',' : thousSep,
                dec = (typeof decSep === 'undefined') ? '.' : decSep,
                s = '',
                toFixedFix = function(n, prec) {
                    var k = Math.pow(10, prec);
                    return '' + Math.round(n * k) / k;
                };

            s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
            if (s[0].length > 3) {
                s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
            }
            if ((s[1] || '').length < prec) {
                s[1] = s[1] || '';
                s[1] += new Array(prec - s[1].length + 1).join('0');
            }
            return s.join(dec);
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        WCEPO_Frontend.init();
    });

})(jQuery);
