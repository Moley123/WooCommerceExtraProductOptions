<?php
/**
 * Admin functionality
 *
 * @package WC_Extra_Product_Options
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class WCEPO_Admin {

    /**
     * Single instance of the class
     *
     * @var WCEPO_Admin
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     *
     * @return WCEPO_Admin
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
        // Add handling fee field to simple products
        add_action('woocommerce_product_options_pricing', array($this, 'add_handling_fee_field'));

        // Add handling fee field to variable products (parent level)
        add_action('woocommerce_product_options_general_product_data', array($this, 'add_handling_fee_field_variable'));

        // Add handling fee field to variations
        add_action('woocommerce_variation_options_pricing', array($this, 'add_variation_handling_fee_field'), 10, 3);

        // Save handling fee for simple products
        add_action('woocommerce_process_product_meta', array($this, 'save_handling_fee_field'));

        // Save handling fee for variations
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_handling_fee_field'), 10, 2);

        // Add admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * Add handling fee field to simple products
     */
    public function add_handling_fee_field() {
        global $post;

        $product = wc_get_product($post->ID);

        // Only show for simple products in pricing section
        if ($product && $product->is_type('simple')) {
            $this->render_handling_fee_field($post->ID);
        }
    }

    /**
     * Add handling fee field to variable products (parent level)
     */
    public function add_handling_fee_field_variable() {
        global $post;

        $product = wc_get_product($post->ID);

        // Only show for variable products
        if ($product && $product->is_type('variable')) {
            echo '<div class="options_group show_if_variable">';
            $this->render_handling_fee_field($post->ID, true);
            echo '</div>';
        }
    }

    /**
     * Render handling fee field
     *
     * @param int  $product_id Product ID
     * @param bool $is_parent  Whether this is the parent product of a variable product
     */
    private function render_handling_fee_field($product_id, $is_parent = false) {
        $handling_fee_label = get_option('wcepo_handling_fee_label', __('Handling Fee', 'wc-extra-product-options'));
        $handling_fee = get_post_meta($product_id, '_wcepo_handling_fee', true);
        $handling_fee_type = get_post_meta($product_id, '_wcepo_handling_fee_type', true);

        if (empty($handling_fee_type)) {
            $handling_fee_type = 'fixed';
        }

        $description = $is_parent
            ? __('Default handling fee for all variations. Can be overridden per variation.', 'wc-extra-product-options')
            : __('Additional fee added to the product price.', 'wc-extra-product-options');

        woocommerce_wp_text_input(array(
            'id'          => '_wcepo_handling_fee',
            'label'       => $handling_fee_label . ' (' . get_woocommerce_currency_symbol() . ')',
            'desc_tip'    => true,
            'description' => $description,
            'type'        => 'text',
            'data_type'   => 'price',
            'value'       => $handling_fee,
        ));

        woocommerce_wp_select(array(
            'id'          => '_wcepo_handling_fee_type',
            'label'       => __('Fee Type', 'wc-extra-product-options'),
            'desc_tip'    => true,
            'description' => __('Choose whether the handling fee is a fixed amount or percentage of product price.', 'wc-extra-product-options'),
            'options'     => array(
                'fixed'      => __('Fixed Amount', 'wc-extra-product-options'),
                'percentage' => __('Percentage', 'wc-extra-product-options'),
            ),
            'value'       => $handling_fee_type,
        ));
    }

    /**
     * Add handling fee field to variations
     *
     * @param int     $loop           Variation loop index
     * @param array   $variation_data Variation data
     * @param WP_Post $variation      Variation post object
     */
    public function add_variation_handling_fee_field($loop, $variation_data, $variation) {
        $handling_fee_label = get_option('wcepo_handling_fee_label', __('Handling Fee', 'wc-extra-product-options'));
        $handling_fee = get_post_meta($variation->ID, '_wcepo_handling_fee', true);
        $handling_fee_type = get_post_meta($variation->ID, '_wcepo_handling_fee_type', true);
        $use_parent_fee = get_post_meta($variation->ID, '_wcepo_use_parent_fee', true);

        if (empty($handling_fee_type)) {
            $handling_fee_type = 'fixed';
        }

        if (empty($use_parent_fee)) {
            $use_parent_fee = 'yes';
        }

        ?>
        <div class="wcepo-variation-handling-fee">
            <p class="form-row form-row-first">
                <label>
                    <input type="checkbox" class="checkbox wcepo_use_parent_fee" name="wcepo_use_parent_fee[<?php echo esc_attr($loop); ?>]" <?php checked($use_parent_fee, 'yes'); ?> value="yes" />
                    <?php esc_html_e('Use parent handling fee', 'wc-extra-product-options'); ?>
                </label>
            </p>
        </div>
        <p class="form-row form-row-first wcepo-variation-fee-field" <?php echo $use_parent_fee === 'yes' ? 'style="display:none;"' : ''; ?>>
            <label><?php echo esc_html($handling_fee_label); ?> (<?php echo get_woocommerce_currency_symbol(); ?>)</label>
            <input type="text" class="wc_input_price" name="wcepo_handling_fee[<?php echo esc_attr($loop); ?>]" value="<?php echo esc_attr($handling_fee); ?>" placeholder="<?php esc_attr_e('Handling fee', 'wc-extra-product-options'); ?>" />
        </p>
        <p class="form-row form-row-last wcepo-variation-fee-type-field" <?php echo $use_parent_fee === 'yes' ? 'style="display:none;"' : ''; ?>>
            <label><?php esc_html_e('Fee Type', 'wc-extra-product-options'); ?></label>
            <select name="wcepo_handling_fee_type[<?php echo esc_attr($loop); ?>]">
                <option value="fixed" <?php selected($handling_fee_type, 'fixed'); ?>><?php esc_html_e('Fixed Amount', 'wc-extra-product-options'); ?></option>
                <option value="percentage" <?php selected($handling_fee_type, 'percentage'); ?>><?php esc_html_e('Percentage', 'wc-extra-product-options'); ?></option>
            </select>
        </p>
        <?php
    }

    /**
     * Save handling fee for simple products
     *
     * @param int $post_id Product ID
     */
    public function save_handling_fee_field($post_id) {
        // Save handling fee
        if (isset($_POST['_wcepo_handling_fee'])) {
            $handling_fee = sanitize_text_field(wp_unslash($_POST['_wcepo_handling_fee']));
            update_post_meta($post_id, '_wcepo_handling_fee', wc_format_decimal($handling_fee));
        }

        // Save handling fee type
        if (isset($_POST['_wcepo_handling_fee_type'])) {
            $handling_fee_type = sanitize_text_field(wp_unslash($_POST['_wcepo_handling_fee_type']));
            update_post_meta($post_id, '_wcepo_handling_fee_type', $handling_fee_type);
        }
    }

    /**
     * Save handling fee for variations
     *
     * @param int $variation_id Variation ID
     * @param int $loop         Loop index
     */
    public function save_variation_handling_fee_field($variation_id, $loop) {
        // Save use parent fee checkbox
        $use_parent_fee = isset($_POST['wcepo_use_parent_fee'][$loop]) ? 'yes' : 'no';
        update_post_meta($variation_id, '_wcepo_use_parent_fee', $use_parent_fee);

        // Save handling fee
        if (isset($_POST['wcepo_handling_fee'][$loop])) {
            $handling_fee = sanitize_text_field(wp_unslash($_POST['wcepo_handling_fee'][$loop]));
            update_post_meta($variation_id, '_wcepo_handling_fee', wc_format_decimal($handling_fee));
        }

        // Save handling fee type
        if (isset($_POST['wcepo_handling_fee_type'][$loop])) {
            $handling_fee_type = sanitize_text_field(wp_unslash($_POST['wcepo_handling_fee_type'][$loop]));
            update_post_meta($variation_id, '_wcepo_handling_fee_type', $handling_fee_type);
        }
    }

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page
     */
    public function enqueue_admin_scripts($hook) {
        global $post;

        if ('post.php' !== $hook && 'post-new.php' !== $hook) {
            return;
        }

        if (!$post || 'product' !== $post->post_type) {
            return;
        }

        wp_enqueue_script(
            'wcepo-admin',
            WCEPO_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WCEPO_VERSION,
            true
        );

        wp_enqueue_style(
            'wcepo-admin',
            WCEPO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WCEPO_VERSION
        );
    }
}
