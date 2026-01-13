<?php
/**
 * Price display functionality
 *
 * @package WC_Extra_Product_Options
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Price Display class
 */
class WCEPO_Price_Display {

    /**
     * Single instance of the class
     *
     * @var WCEPO_Price_Display
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     *
     * @return WCEPO_Price_Display
     */
    public static function get_instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Filter variable product price display
        add_filter('woocommerce_variable_price_html', array($this, 'modify_variable_price_html'), 10, 2);

        // Filter single product price display
        add_filter('woocommerce_get_price_html', array($this, 'modify_price_html'), 10, 2);

        // Filter variation price in JSON data for JavaScript
        add_filter('woocommerce_available_variation', array($this, 'modify_variation_data'), 10, 3);
    }

    /**
     * Get handling fee for a product
     *
     * @param int     $product_id Product ID
     * @param WC_Product $product   Product object (optional)
     * @return float
     */
    public function get_handling_fee($product_id, $product = null) {
        if (!$product) {
            $product = wc_get_product($product_id);
        }

        if (!$product) {
            return 0;
        }

        $handling_fee = 0;
        $fee_value = 0;
        $fee_type = 'fixed';

        // For variations, check if using parent fee
        if ($product->is_type('variation')) {
            $use_parent_fee = get_post_meta($product_id, '_wcepo_use_parent_fee', true);

            if ($use_parent_fee === 'yes' || empty($use_parent_fee)) {
                // Get parent product fee
                $parent_id = $product->get_parent_id();
                $fee_value = get_post_meta($parent_id, '_wcepo_handling_fee', true);
                $fee_type = get_post_meta($parent_id, '_wcepo_handling_fee_type', true);
            } else {
                // Get variation's own fee
                $fee_value = get_post_meta($product_id, '_wcepo_handling_fee', true);
                $fee_type = get_post_meta($product_id, '_wcepo_handling_fee_type', true);
            }
        } else {
            // Simple product or variable product parent
            $fee_value = get_post_meta($product_id, '_wcepo_handling_fee', true);
            $fee_type = get_post_meta($product_id, '_wcepo_handling_fee_type', true);
        }

        if (empty($fee_value)) {
            return 0;
        }

        $fee_value = floatval($fee_value);
        $fee_type = empty($fee_type) ? 'fixed' : $fee_type;

        if ($fee_type === 'percentage') {
            // Calculate percentage of product price
            $price = $product->get_price();
            $handling_fee = ($price * $fee_value) / 100;
        } else {
            $handling_fee = $fee_value;
        }

        return $handling_fee;
    }

    /**
     * Get the total price including handling fee
     *
     * @param WC_Product $product Product object
     * @return float
     */
    public function get_total_price($product) {
        $price = $product->get_price();
        $include_handling = get_option('wcepo_include_handling_in_price', 'yes');

        if ($include_handling === 'yes') {
            $handling_fee = $this->get_handling_fee($product->get_id(), $product);
            $price += $handling_fee;
        }

        return $price;
    }

    /**
     * Get the minimum total price for a variable product
     *
     * @param WC_Product_Variable $product Variable product
     * @return float
     */
    public function get_min_total_price($product) {
        if (!$product->is_type('variable')) {
            return $this->get_total_price($product);
        }

        $min_price = PHP_FLOAT_MAX;
        $variations = $product->get_available_variations();

        foreach ($variations as $variation_data) {
            $variation = wc_get_product($variation_data['variation_id']);

            if (!$variation || !$variation->is_purchasable()) {
                continue;
            }

            $total_price = $this->get_total_price($variation);

            if ($total_price < $min_price) {
                $min_price = $total_price;
            }
        }

        return $min_price === PHP_FLOAT_MAX ? 0 : $min_price;
    }

    /**
     * Modify variable product price HTML
     *
     * @param string              $price   Price HTML
     * @param WC_Product_Variable $product Product object
     * @return string
     */
    public function modify_variable_price_html($price, $product) {
        if (!is_product()) {
            return $price;
        }

        $show_from = get_option('wcepo_show_from_text', 'yes');
        $from_text = get_option('wcepo_from_text', __('From', 'wc-extra-product-options'));

        // Get minimum total price (including handling fee if applicable)
        $min_price = $this->get_min_total_price($product);

        if ($min_price > 0) {
            $price_html = wc_price($min_price);

            if ($show_from === 'yes') {
                $price_html = '<span class="wcepo-from-text">' . esc_html($from_text) . '</span> ' . $price_html;
            }

            return '<span class="wcepo-price-wrapper">' . $price_html . '</span>';
        }

        return $price;
    }

    /**
     * Modify price HTML for simple products
     *
     * @param string     $price   Price HTML
     * @param WC_Product $product Product object
     * @return string
     */
    public function modify_price_html($price, $product) {
        // Only modify on single product pages
        if (!is_product()) {
            return $price;
        }

        // Skip if this is a variable product (handled by modify_variable_price_html)
        if ($product->is_type('variable')) {
            return $price;
        }

        // Skip variations - they're handled separately
        if ($product->is_type('variation')) {
            return $price;
        }

        $include_handling = get_option('wcepo_include_handling_in_price', 'yes');

        if ($include_handling !== 'yes') {
            return $price;
        }

        $handling_fee = $this->get_handling_fee($product->get_id(), $product);

        if ($handling_fee <= 0) {
            return $price;
        }

        $total_price = $product->get_price() + $handling_fee;
        return '<span class="wcepo-price-wrapper">' . wc_price($total_price) . '</span>';
    }

    /**
     * Modify variation data for JavaScript
     *
     * @param array                $data      Variation data
     * @param WC_Product_Variable  $product   Parent product
     * @param WC_Product_Variation $variation Variation product
     * @return array
     */
    public function modify_variation_data($data, $product, $variation) {
        $include_handling = get_option('wcepo_include_handling_in_price', 'yes');
        $handling_fee = $this->get_handling_fee($variation->get_id(), $variation);

        // Add custom data for JavaScript
        $data['wcepo_handling_fee'] = $handling_fee;
        $data['wcepo_include_handling'] = $include_handling;

        if ($include_handling === 'yes' && $handling_fee > 0) {
            $total_price = $variation->get_price() + $handling_fee;
            $data['wcepo_total_price'] = $total_price;
            $data['wcepo_total_price_html'] = wc_price($total_price);

            // Update display price HTML
            $data['price_html'] = '<span class="wcepo-price-wrapper">' . wc_price($total_price) . '</span>';
        }

        return $data;
    }
}
