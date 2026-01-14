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
     * Flag to prevent recursion
     *
     * @var bool
     */
    private static $calculating_price = false;

    /**
     * VCC Frontend Display instance (if available)
     *
     * @var VCC_Frontend_Display|null
     */
    private $vcc_frontend = null;

    /**
     * VCC Currency Converter instance (if available)
     *
     * @var VCC_Currency_Converter|null
     */
    private $vcc_converter = null;

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
        $this->init_vcc_integration();
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

        // Register shortcode for price breakdown
        add_shortcode('wcepo_price_breakdown', array($this, 'shortcode_price_breakdown'));
    }

    /**
     * Initialize VCC (Vignette Currency Converter) integration
     */
    private function init_vcc_integration() {
        // Check if VCC plugin is active and get instances
        if (class_exists('VCC_Frontend_Display')) {
            $this->vcc_frontend = VCC_Frontend_Display::get_instance();
        }
        if (class_exists('VCC_Currency_Converter')) {
            $this->vcc_converter = VCC_Currency_Converter::get_instance();
        }
    }

    /**
     * Check if VCC currency converter is available
     *
     * @return bool
     */
    private function is_vcc_active() {
        return $this->vcc_frontend !== null && $this->vcc_converter !== null;
    }

    /**
     * Get the selected currency from VCC
     * Checks VCC's method first, then falls back to reading cookies directly
     *
     * @return string Currency code (e.g., 'GBP', 'EUR', 'CHF')
     */
    public function get_selected_currency() {
        // First try VCC's method
        if ($this->vcc_frontend && method_exists($this->vcc_frontend, 'get_selected_currency')) {
            $currency = $this->vcc_frontend->get_selected_currency();
            if ($currency && $currency !== 'GBP') {
                return $currency;
            }
        }

        // Fallback: Check cookie directly (in case VCC session isn't initialized yet)
        if (isset($_COOKIE['vcc_selected_currency'])) {
            $cookie_currency = sanitize_text_field($_COOKIE['vcc_selected_currency']);
            if (!empty($cookie_currency)) {
                return $cookie_currency;
            }
        }

        // Fallback: Check session directly
        if (isset($_SESSION['vcc_selected_currency'])) {
            return sanitize_text_field($_SESSION['vcc_selected_currency']);
        }

        return 'GBP';
    }

    /**
     * Convert a GBP price to the selected currency using VCC
     *
     * @param float $gbp_price Price in GBP
     * @return float Converted price
     */
    public function convert_price_for_display($gbp_price) {
        if (!$this->is_vcc_active()) {
            return $gbp_price;
        }

        $currency = $this->get_selected_currency();

        // If already GBP, no conversion needed
        if ($currency === 'GBP') {
            return $gbp_price;
        }

        // Use VCC converter to convert from GBP to selected currency
        if ($this->vcc_converter && method_exists($this->vcc_converter, 'convert_from_gbp')) {
            $converted = $this->vcc_converter->convert_from_gbp($gbp_price, $currency);
            if (!is_wp_error($converted)) {
                return $converted;
            }
        }

        return $gbp_price;
    }

    /**
     * Format price with currency - uses VCC formatting if available
     *
     * @param float $price Price to format
     * @return string Formatted price HTML
     */
    public function format_price_html($price) {
        if (!$this->is_vcc_active()) {
            return wc_price($price);
        }

        $currency = $this->get_selected_currency();

        // If GBP, use standard WooCommerce formatting
        if ($currency === 'GBP') {
            return wc_price($price);
        }

        // Format with the selected currency
        if ($this->vcc_converter && method_exists($this->vcc_converter, 'get_currency_symbol')) {
            $symbol = $this->vcc_converter->get_currency_symbol($currency);
            $decimals = ($currency === 'JPY') ? 0 : 2;
            $formatted = number_format($price, $decimals);

            // Format based on currency
            switch ($currency) {
                case 'EUR':
                    $formatted_price = '€' . $formatted;
                    break;
                case 'USD':
                case 'CAD':
                case 'AUD':
                    $formatted_price = $symbol . $formatted;
                    break;
                case 'JPY':
                    $formatted_price = '¥' . $formatted;
                    break;
                default:
                    $formatted_price = $formatted . ' ' . $currency;
            }

            return '<span class="woocommerce-Price-amount amount"><bdi>' . $formatted_price . '</bdi></span>';
        }

        return wc_price($price);
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

        // Use get_children() instead of get_available_variations() to avoid recursion
        $variation_ids = $product->get_children();

        if (empty($variation_ids)) {
            return 0;
        }

        $min_price = PHP_FLOAT_MAX;
        $include_handling = get_option('wcepo_include_handling_in_price', 'yes');

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);

            if (!$variation || !$variation->is_purchasable() || !$variation->is_in_stock()) {
                continue;
            }

            $price = (float) $variation->get_price();

            if ($price <= 0) {
                continue;
            }

            // Add handling fee if applicable
            if ($include_handling === 'yes') {
                $handling_fee = $this->get_handling_fee($variation_id, $variation);
                $price += $handling_fee;
            }

            if ($price < $min_price) {
                $min_price = $price;
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
        // Prevent recursion
        if (self::$calculating_price) {
            return $price;
        }

        if (!is_product()) {
            return $price;
        }

        // Ensure we have a valid product object
        if (!$product || !is_a($product, 'WC_Product')) {
            return $price;
        }

        self::$calculating_price = true;

        $show_from = get_option('wcepo_show_from_text', 'yes');
        $from_text = get_option('wcepo_from_text', __('From', 'wc-extra-product-options'));

        // Get minimum total price in GBP (including handling fee if applicable)
        $min_price_gbp = $this->get_min_total_price($product);

        self::$calculating_price = false;

        if ($min_price_gbp > 0) {
            // Convert to selected currency if VCC is active
            $display_price = $this->convert_price_for_display($min_price_gbp);

            // Format with appropriate currency
            $price_html = $this->format_price_html($display_price);

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
        // Prevent recursion
        if (self::$calculating_price) {
            return $price;
        }

        // Only modify on single product pages
        if (!is_product()) {
            return $price;
        }

        // Ensure we have a valid product object
        if (!$product || !is_a($product, 'WC_Product')) {
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

        // Calculate total price in GBP
        $total_price_gbp = $product->get_price() + $handling_fee;

        // Convert to selected currency if VCC is active
        $display_price = $this->convert_price_for_display($total_price_gbp);

        // Format with appropriate currency
        return '<span class="wcepo-price-wrapper">' . $this->format_price_html($display_price) . '</span>';
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
            // Calculate total price in GBP
            $total_price_gbp = $variation->get_price() + $handling_fee;

            // Convert to selected currency if VCC is active
            $display_price = $this->convert_price_for_display($total_price_gbp);

            $data['wcepo_total_price'] = $total_price_gbp;
            $data['wcepo_total_price_display'] = $display_price;
            $data['wcepo_total_price_html'] = $this->format_price_html($display_price);

            // Update display price HTML
            $data['price_html'] = '<span class="wcepo-price-wrapper">' . $this->format_price_html($display_price) . '</span>';
        }

        // Add currency info for JavaScript
        $data['wcepo_currency'] = $this->get_selected_currency();
        $data['wcepo_vcc_active'] = $this->is_vcc_active();

        return $data;
    }

    /**
     * Get price breakdown for a product (base price + handling fee)
     *
     * @param WC_Product $product Product object
     * @return array Array with 'base_price', 'handling_fee', 'total', and formatted versions
     */
    public function get_price_breakdown($product) {
        if (!$product || !is_a($product, 'WC_Product')) {
            return null;
        }

        $base_price_gbp = (float) $product->get_price();
        $handling_fee_gbp = $this->get_handling_fee($product->get_id(), $product);
        $total_gbp = $base_price_gbp + $handling_fee_gbp;

        // Convert to display currency
        $base_price_display = $this->convert_price_for_display($base_price_gbp);
        $handling_fee_display = $this->convert_price_for_display($handling_fee_gbp);
        $total_display = $this->convert_price_for_display($total_gbp);

        $handling_fee_label = get_option('wcepo_handling_fee_label', __('Handling Fee', 'wc-extra-product-options'));

        return array(
            'base_price_gbp' => $base_price_gbp,
            'handling_fee_gbp' => $handling_fee_gbp,
            'total_gbp' => $total_gbp,
            'base_price' => $base_price_display,
            'handling_fee' => $handling_fee_display,
            'total' => $total_display,
            'base_price_html' => $this->format_price_html($base_price_display),
            'handling_fee_html' => $this->format_price_html($handling_fee_display),
            'total_html' => $this->format_price_html($total_display),
            'handling_fee_label' => $handling_fee_label,
            'currency' => $this->get_selected_currency(),
            'has_handling_fee' => $handling_fee_gbp > 0,
        );
    }

    /**
     * Render price breakdown HTML
     *
     * @param WC_Product|int $product Product object or ID
     * @param array $args Optional arguments (show_total, wrapper_class)
     * @return string HTML output
     */
    public function render_price_breakdown($product, $args = array()) {
        if (is_numeric($product)) {
            $product = wc_get_product($product);
        }

        if (!$product) {
            return '';
        }

        $defaults = array(
            'show_total' => true,
            'wrapper_class' => 'wcepo-price-breakdown',
            'layout' => 'vertical', // 'vertical' or 'horizontal'
        );
        $args = wp_parse_args($args, $defaults);

        $breakdown = $this->get_price_breakdown($product);

        if (!$breakdown) {
            return '';
        }

        $layout_class = $args['layout'] === 'horizontal' ? 'wcepo-breakdown-horizontal' : 'wcepo-breakdown-vertical';

        ob_start();
        ?>
        <div class="<?php echo esc_attr($args['wrapper_class'] . ' ' . $layout_class); ?>" data-product-id="<?php echo esc_attr($product->get_id()); ?>" data-base-price-gbp="<?php echo esc_attr($breakdown['base_price_gbp']); ?>" data-handling-fee-gbp="<?php echo esc_attr($breakdown['handling_fee_gbp']); ?>">
            <div class="wcepo-breakdown-row wcepo-base-price">
                <span class="wcepo-breakdown-label"><?php echo esc_html($product->get_name()); ?>:</span>
                <span class="wcepo-breakdown-value"><?php echo $breakdown['base_price_html']; ?></span>
            </div>
            <?php if ($breakdown['has_handling_fee']) : ?>
            <div class="wcepo-breakdown-row wcepo-handling-fee">
                <span class="wcepo-breakdown-label"><?php echo esc_html($breakdown['handling_fee_label']); ?>:</span>
                <span class="wcepo-breakdown-value"><?php echo $breakdown['handling_fee_html']; ?></span>
            </div>
            <?php endif; ?>
            <?php if ($args['show_total'] && $breakdown['has_handling_fee']) : ?>
            <div class="wcepo-breakdown-row wcepo-total">
                <span class="wcepo-breakdown-label"><?php esc_html_e('Total', 'wc-extra-product-options'); ?>:</span>
                <span class="wcepo-breakdown-value wcepo-total-price"><?php echo $breakdown['total_html']; ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode handler for price breakdown
     * Usage: [wcepo_price_breakdown] or [wcepo_price_breakdown product_id="123" show_total="yes" layout="horizontal"]
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function shortcode_price_breakdown($atts) {
        $atts = shortcode_atts(array(
            'product_id' => 0,
            'show_total' => 'yes',
            'layout' => 'vertical',
            'class' => '',
        ), $atts, 'wcepo_price_breakdown');

        // Get product - use current product if no ID specified
        $product_id = intval($atts['product_id']);
        if (!$product_id) {
            global $product;
            if ($product && is_a($product, 'WC_Product')) {
                $product_id = $product->get_id();
            }
        }

        if (!$product_id) {
            return '';
        }

        $wrapper_class = 'wcepo-price-breakdown';
        if (!empty($atts['class'])) {
            $wrapper_class .= ' ' . sanitize_html_class($atts['class']);
        }

        return $this->render_price_breakdown($product_id, array(
            'show_total' => $atts['show_total'] === 'yes',
            'wrapper_class' => $wrapper_class,
            'layout' => $atts['layout'],
        ));
    }
}
