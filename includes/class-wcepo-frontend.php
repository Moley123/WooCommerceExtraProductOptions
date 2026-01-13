<?php
/**
 * Frontend functionality
 *
 * @package WC_Extra_Product_Options
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend class
 */
class WCEPO_Frontend {

    /**
     * Single instance of the class
     *
     * @var WCEPO_Frontend
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     *
     * @return WCEPO_Frontend
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
        // Enqueue frontend scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));

        // Display handling fee info on product page
        add_action('woocommerce_single_product_summary', array($this, 'display_handling_fee_info'), 15);

        // Add handling fee to cart item data
        add_filter('woocommerce_add_cart_item_data', array($this, 'add_handling_fee_to_cart'), 10, 3);

        // Display handling fee in cart
        add_filter('woocommerce_get_item_data', array($this, 'display_handling_fee_in_cart'), 10, 2);

        // Add handling fee to cart item price
        add_action('woocommerce_before_calculate_totals', array($this, 'add_handling_fee_to_price'), 10, 1);

        // Save handling fee to order item meta
        add_action('woocommerce_checkout_create_order_line_item', array($this, 'save_handling_fee_to_order'), 10, 4);
    }

    /**
     * Enqueue frontend scripts and styles
     */
    public function enqueue_scripts() {
        if (!is_product()) {
            return;
        }

        global $product;

        if (!$product) {
            $product = wc_get_product(get_the_ID());
        }

        if (!$product) {
            return;
        }

        // Enqueue JavaScript
        wp_enqueue_script(
            'wcepo-frontend',
            WCEPO_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery', 'wc-add-to-cart-variation'),
            WCEPO_VERSION,
            true
        );

        // Enqueue CSS
        wp_enqueue_style(
            'wcepo-frontend',
            WCEPO_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WCEPO_VERSION
        );

        // Localize script with settings
        $price_display = WCEPO_Price_Display::get_instance();

        $localize_data = array(
            'from_text'          => get_option('wcepo_from_text', __('From', 'wc-extra-product-options')),
            'show_from_text'     => get_option('wcepo_show_from_text', 'yes'),
            'include_handling'   => get_option('wcepo_include_handling_in_price', 'yes'),
            'show_fee_separate'  => get_option('wcepo_show_handling_fee_separately', 'no'),
            'handling_fee_label' => get_option('wcepo_handling_fee_label', __('Handling Fee', 'wc-extra-product-options')),
            'currency_symbol'    => get_woocommerce_currency_symbol(),
            'currency_position'  => get_option('woocommerce_currency_pos'),
            'thousand_sep'       => wc_get_price_thousand_separator(),
            'decimal_sep'        => wc_get_price_decimal_separator(),
            'decimals'           => wc_get_price_decimals(),
            'is_variable'        => $product->is_type('variable'),
            'product_id'         => $product->get_id(),
        );

        // Get minimum price for variable products
        if ($product->is_type('variable')) {
            $localize_data['min_price'] = $price_display->get_min_total_price($product);
            $localize_data['min_price_html'] = wc_price($localize_data['min_price']);
        }

        wp_localize_script('wcepo-frontend', 'wcepo_params', $localize_data);
    }

    /**
     * Display handling fee info on product page
     */
    public function display_handling_fee_info() {
        $show_separate = get_option('wcepo_show_handling_fee_separately', 'no');

        if ($show_separate !== 'yes') {
            return;
        }

        global $product;

        if (!$product) {
            return;
        }

        $price_display = WCEPO_Price_Display::get_instance();
        $handling_fee_label = get_option('wcepo_handling_fee_label', __('Handling Fee', 'wc-extra-product-options'));

        if ($product->is_type('variable')) {
            // For variable products, show a placeholder that will be updated by JavaScript
            ?>
            <div class="wcepo-handling-fee-info" style="display:none;">
                <span class="wcepo-handling-fee-label"><?php echo esc_html($handling_fee_label); ?>:</span>
                <span class="wcepo-handling-fee-amount"></span>
            </div>
            <?php
        } else {
            $handling_fee = $price_display->get_handling_fee($product->get_id(), $product);

            if ($handling_fee > 0) {
                ?>
                <div class="wcepo-handling-fee-info">
                    <span class="wcepo-handling-fee-label"><?php echo esc_html($handling_fee_label); ?>:</span>
                    <span class="wcepo-handling-fee-amount"><?php echo wc_price($handling_fee); ?></span>
                </div>
                <?php
            }
        }
    }

    /**
     * Add handling fee data to cart item
     *
     * @param array $cart_item_data Cart item data
     * @param int   $product_id     Product ID
     * @param int   $variation_id   Variation ID
     * @return array
     */
    public function add_handling_fee_to_cart($cart_item_data, $product_id, $variation_id) {
        $price_display = WCEPO_Price_Display::get_instance();

        $actual_product_id = $variation_id ? $variation_id : $product_id;
        $product = wc_get_product($actual_product_id);

        if (!$product) {
            return $cart_item_data;
        }

        $handling_fee = $price_display->get_handling_fee($actual_product_id, $product);

        if ($handling_fee > 0) {
            $cart_item_data['wcepo_handling_fee'] = $handling_fee;
        }

        return $cart_item_data;
    }

    /**
     * Display handling fee in cart
     *
     * @param array $item_data Cart item data for display
     * @param array $cart_item Cart item
     * @return array
     */
    public function display_handling_fee_in_cart($item_data, $cart_item) {
        $show_separate = get_option('wcepo_show_handling_fee_separately', 'no');
        $include_handling = get_option('wcepo_include_handling_in_price', 'yes');

        // Only show separately if not included in price display
        if ($show_separate === 'yes' && $include_handling !== 'yes' && isset($cart_item['wcepo_handling_fee']) && $cart_item['wcepo_handling_fee'] > 0) {
            $handling_fee_label = get_option('wcepo_handling_fee_label', __('Handling Fee', 'wc-extra-product-options'));

            $item_data[] = array(
                'key'   => $handling_fee_label,
                'value' => wc_price($cart_item['wcepo_handling_fee']),
            );
        }

        return $item_data;
    }

    /**
     * Add handling fee to cart item price
     *
     * @param WC_Cart $cart Cart object
     */
    public function add_handling_fee_to_price($cart) {
        if (is_admin() && !defined('DOING_AJAX')) {
            return;
        }

        if (did_action('woocommerce_before_calculate_totals') >= 2) {
            return;
        }

        $include_handling = get_option('wcepo_include_handling_in_price', 'yes');

        if ($include_handling !== 'yes') {
            return;
        }

        foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
            if (isset($cart_item['wcepo_handling_fee']) && $cart_item['wcepo_handling_fee'] > 0) {
                $product = $cart_item['data'];
                $original_price = $product->get_price();
                $new_price = $original_price + $cart_item['wcepo_handling_fee'];
                $product->set_price($new_price);
            }
        }
    }

    /**
     * Save handling fee to order item meta
     *
     * @param WC_Order_Item_Product $item          Order item
     * @param string                $cart_item_key Cart item key
     * @param array                 $values        Cart item values
     * @param WC_Order              $order         Order object
     */
    public function save_handling_fee_to_order($item, $cart_item_key, $values, $order) {
        if (isset($values['wcepo_handling_fee']) && $values['wcepo_handling_fee'] > 0) {
            $handling_fee_label = get_option('wcepo_handling_fee_label', __('Handling Fee', 'wc-extra-product-options'));
            $item->add_meta_data($handling_fee_label, wc_price($values['wcepo_handling_fee']), true);
        }
    }
}
