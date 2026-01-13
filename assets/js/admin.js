/**
 * WooCommerce Extra Product Options - Admin JavaScript
 *
 * Handles admin functionality for variation handling fees
 */

(function($) {
    'use strict';

    var WCEPO_Admin = {
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Toggle variation fee fields based on "Use parent fee" checkbox
            $(document).on('change', '.wcepo_use_parent_fee', function() {
                self.toggleVariationFeeFields($(this));
            });

            // Initialize on variation load
            $(document).on('woocommerce_variations_loaded', function() {
                self.initVariationFields();
            });

            // Initialize on variation added
            $(document).on('woocommerce_variations_added', function() {
                self.initVariationFields();
            });
        },

        /**
         * Toggle variation fee fields visibility
         *
         * @param {jQuery} $checkbox The checkbox element
         */
        toggleVariationFeeFields: function($checkbox) {
            var $wrapper = $checkbox.closest('.woocommerce_variation');
            var $feeField = $wrapper.find('.wcepo-variation-fee-field');
            var $feeTypeField = $wrapper.find('.wcepo-variation-fee-type-field');

            if ($checkbox.is(':checked')) {
                $feeField.hide();
                $feeTypeField.hide();
            } else {
                $feeField.show();
                $feeTypeField.show();
            }
        },

        /**
         * Initialize variation fields on load
         */
        initVariationFields: function() {
            var self = this;

            $('.wcepo_use_parent_fee').each(function() {
                self.toggleVariationFeeFields($(this));
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        WCEPO_Admin.init();
    });

})(jQuery);
