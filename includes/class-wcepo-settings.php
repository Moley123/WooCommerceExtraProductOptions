<?php
/**
 * Settings functionality
 *
 * @package WC_Extra_Product_Options
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
class WCEPO_Settings {

    /**
     * Single instance of the class
     *
     * @var WCEPO_Settings
     */
    private static $instance = null;

    /**
     * Settings page slug
     *
     * @var string
     */
    private $settings_page = 'wcepo-settings';

    /**
     * Get single instance of the class
     *
     * @return WCEPO_Settings
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
     * Parent menu slug for EMEL plugins
     *
     * @var string
     */
    private $parent_slug = 'emel-wp-plugins';

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Add settings page under EMEL WP Plugins menu
        add_action('admin_menu', array($this, 'add_settings_page'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . WCEPO_PLUGIN_BASENAME, array($this, 'add_settings_link'));
    }

    /**
     * Add settings page to admin menu under EMEL WP Plugins
     */
    public function add_settings_page() {
        // Create the top-level EMEL WP Plugins menu if it doesn't exist
        if (!isset($GLOBALS['admin_page_hooks'][$this->parent_slug])) {
            add_menu_page(
                __('EMEL WP Plugins', 'wc-extra-product-options'),
                __('EMEL WP Plugins', 'wc-extra-product-options'),
                'manage_options',
                $this->parent_slug,
                '__return_null',
                'dashicons-admin-plugins',
                58
            );
            // Remove the duplicate submenu item that WordPress creates
            remove_submenu_page($this->parent_slug, $this->parent_slug);
        }

        // Add our settings page as a submenu
        add_submenu_page(
            $this->parent_slug,
            __('Extra Product Options', 'wc-extra-product-options'),
            __('Extra Product Options', 'wc-extra-product-options'),
            'manage_options',
            $this->settings_page,
            array($this, 'render_settings_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        register_setting('wcepo_settings', 'wcepo_handling_fee_label');
        register_setting('wcepo_settings', 'wcepo_from_text');
        register_setting('wcepo_settings', 'wcepo_show_from_text');
        register_setting('wcepo_settings', 'wcepo_include_handling_in_price');
        register_setting('wcepo_settings', 'wcepo_show_handling_fee_separately');
        register_setting('wcepo_settings', 'wcepo_hide_default_price');
        register_setting('wcepo_settings', 'wcepo_hide_reset_link');
        register_setting('wcepo_settings', 'wcepo_format_sale_price');

        // Add settings sections
        add_settings_section(
            'wcepo_price_display_section',
            __('Price Display Settings', 'wc-extra-product-options'),
            array($this, 'price_display_section_callback'),
            $this->settings_page
        );

        add_settings_section(
            'wcepo_variable_product_section',
            __('Variable Product Settings', 'wc-extra-product-options'),
            array($this, 'variable_product_section_callback'),
            $this->settings_page
        );

        add_settings_section(
            'wcepo_handling_fee_section',
            __('Handling Fee Settings', 'wc-extra-product-options'),
            array($this, 'handling_fee_section_callback'),
            $this->settings_page
        );

        // Price display fields
        add_settings_field(
            'wcepo_from_text',
            __('From Text', 'wc-extra-product-options'),
            array($this, 'from_text_field_callback'),
            $this->settings_page,
            'wcepo_price_display_section'
        );

        add_settings_field(
            'wcepo_show_from_text',
            __('Show "From" Text', 'wc-extra-product-options'),
            array($this, 'show_from_text_field_callback'),
            $this->settings_page,
            'wcepo_price_display_section'
        );

        add_settings_field(
            'wcepo_format_sale_price',
            __('Format Sale Price', 'wc-extra-product-options'),
            array($this, 'format_sale_price_field_callback'),
            $this->settings_page,
            'wcepo_price_display_section'
        );

        // Variable product fields
        add_settings_field(
            'wcepo_hide_default_price',
            __('Hide Default Price', 'wc-extra-product-options'),
            array($this, 'hide_default_price_field_callback'),
            $this->settings_page,
            'wcepo_variable_product_section'
        );

        add_settings_field(
            'wcepo_hide_reset_link',
            __('Hide Reset Link', 'wc-extra-product-options'),
            array($this, 'hide_reset_link_field_callback'),
            $this->settings_page,
            'wcepo_variable_product_section'
        );

        // Handling fee fields
        add_settings_field(
            'wcepo_handling_fee_label',
            __('Handling Fee Label', 'wc-extra-product-options'),
            array($this, 'handling_fee_label_field_callback'),
            $this->settings_page,
            'wcepo_handling_fee_section'
        );

        add_settings_field(
            'wcepo_include_handling_in_price',
            __('Include in Price Display', 'wc-extra-product-options'),
            array($this, 'include_handling_in_price_field_callback'),
            $this->settings_page,
            'wcepo_handling_fee_section'
        );

        add_settings_field(
            'wcepo_show_handling_fee_separately',
            __('Show Fee Separately', 'wc-extra-product-options'),
            array($this, 'show_handling_fee_separately_field_callback'),
            $this->settings_page,
            'wcepo_handling_fee_section'
        );
    }

    /**
     * Price display section callback
     */
    public function price_display_section_callback() {
        echo '<p>' . esc_html__('Configure how prices are displayed for variable products.', 'wc-extra-product-options') . '</p>';
    }

    /**
     * Variable product section callback
     */
    public function variable_product_section_callback() {
        echo '<p>' . esc_html__('Configure settings specific to variable products.', 'wc-extra-product-options') . '</p>';
    }

    /**
     * Handling fee section callback
     */
    public function handling_fee_section_callback() {
        echo '<p>' . esc_html__('Configure handling fee settings.', 'wc-extra-product-options') . '</p>';
    }

    /**
     * From text field callback
     */
    public function from_text_field_callback() {
        $value = get_option('wcepo_from_text', __('From', 'wc-extra-product-options'));
        ?>
        <input type="text" name="wcepo_from_text" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Text displayed before the minimum price (e.g., "From", "Starting at").', 'wc-extra-product-options'); ?></p>
        <?php
    }

    /**
     * Show from text field callback
     */
    public function show_from_text_field_callback() {
        $value = get_option('wcepo_show_from_text', 'yes');
        ?>
        <label>
            <input type="checkbox" name="wcepo_show_from_text" value="yes" <?php checked($value, 'yes'); ?> />
            <?php esc_html_e('Display "From" text before the minimum price for variable products.', 'wc-extra-product-options'); ?>
        </label>
        <?php
    }

    /**
     * Format sale price field callback
     */
    public function format_sale_price_field_callback() {
        $value = get_option('wcepo_format_sale_price', 'no');
        ?>
        <label>
            <input type="checkbox" name="wcepo_format_sale_price" value="yes" <?php checked($value, 'yes'); ?> />
            <?php esc_html_e('Show regular price and sale price format for products on sale.', 'wc-extra-product-options'); ?>
        </label>
        <p class="description"><?php esc_html_e('For example: From <del>£40</del> £38', 'wc-extra-product-options'); ?></p>
        <?php
    }

    /**
     * Hide default price field callback
     */
    public function hide_default_price_field_callback() {
        $value = get_option('wcepo_hide_default_price', 'no');
        ?>
        <label>
            <input type="checkbox" name="wcepo_hide_default_price" value="yes" <?php checked($value, 'yes'); ?> />
            <?php esc_html_e("Don't display the default variation price range.", 'wc-extra-product-options'); ?>
        </label>
        <p class="description"><?php esc_html_e('Hides the price until a variation is selected.', 'wc-extra-product-options'); ?></p>
        <?php
    }

    /**
     * Hide reset link field callback
     */
    public function hide_reset_link_field_callback() {
        $value = get_option('wcepo_hide_reset_link', 'no');
        ?>
        <label>
            <input type="checkbox" name="wcepo_hide_reset_link" value="yes" <?php checked($value, 'yes'); ?> />
            <?php esc_html_e('Remove "Clear" link on single product page.', 'wc-extra-product-options'); ?>
        </label>
        <p class="description"><?php esc_html_e('Hides the clear/reset link that appears after selecting a variation.', 'wc-extra-product-options'); ?></p>
        <?php
    }

    /**
     * Handling fee label field callback
     */
    public function handling_fee_label_field_callback() {
        $value = get_option('wcepo_handling_fee_label', __('Handling Fee', 'wc-extra-product-options'));
        ?>
        <input type="text" name="wcepo_handling_fee_label" value="<?php echo esc_attr($value); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Label displayed for the handling fee (e.g., "Handling Fee", "Processing Fee", "Service Charge").', 'wc-extra-product-options'); ?></p>
        <?php
    }

    /**
     * Include handling in price field callback
     */
    public function include_handling_in_price_field_callback() {
        $value = get_option('wcepo_include_handling_in_price', 'yes');
        ?>
        <label>
            <input type="checkbox" name="wcepo_include_handling_in_price" value="yes" <?php checked($value, 'yes'); ?> />
            <?php esc_html_e('Include handling fee in the displayed product price.', 'wc-extra-product-options'); ?>
        </label>
        <p class="description"><?php esc_html_e('When enabled, the price shown on the product page will include the handling fee.', 'wc-extra-product-options'); ?></p>
        <?php
    }

    /**
     * Show handling fee separately field callback
     */
    public function show_handling_fee_separately_field_callback() {
        $value = get_option('wcepo_show_handling_fee_separately', 'no');
        ?>
        <label>
            <input type="checkbox" name="wcepo_show_handling_fee_separately" value="yes" <?php checked($value, 'yes'); ?> />
            <?php esc_html_e('Show handling fee as a separate line item on the product page.', 'wc-extra-product-options'); ?>
        </label>
        <p class="description"><?php esc_html_e('When enabled, the handling fee will be displayed separately below the product price.', 'wc-extra-product-options'); ?></p>
        <?php
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('wcepo_settings');
                do_settings_sections($this->settings_page);
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Add settings link to plugins page
     *
     * @param array $links Plugin action links
     * @return array
     */
    public function add_settings_link($links) {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            admin_url('admin.php?page=' . $this->settings_page),
            __('Settings', 'wc-extra-product-options')
        );
        array_unshift($links, $settings_link);
        return $links;
    }

}
