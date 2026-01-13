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
     * Initialize hooks
     */
    private function init_hooks() {
        // Add settings page
        add_action('admin_menu', array($this, 'add_settings_page'));

        // Register settings
        add_action('admin_init', array($this, 'register_settings'));

        // Add settings link to plugins page
        add_filter('plugin_action_links_' . WCEPO_PLUGIN_BASENAME, array($this, 'add_settings_link'));

        // Add WooCommerce settings tab
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        add_action('woocommerce_settings_tabs_wcepo', array($this, 'settings_tab_content'));
        add_action('woocommerce_update_options_wcepo', array($this, 'update_settings'));
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Extra Product Options', 'wc-extra-product-options'),
            __('Extra Product Options', 'wc-extra-product-options'),
            'manage_woocommerce',
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

        // Add settings sections
        add_settings_section(
            'wcepo_price_display_section',
            __('Price Display Settings', 'wc-extra-product-options'),
            array($this, 'price_display_section_callback'),
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

    /**
     * Add WooCommerce settings tab
     *
     * @param array $tabs Settings tabs
     * @return array
     */
    public function add_settings_tab($tabs) {
        $tabs['wcepo'] = __('Extra Product Options', 'wc-extra-product-options');
        return $tabs;
    }

    /**
     * Settings tab content
     */
    public function settings_tab_content() {
        woocommerce_admin_fields($this->get_settings());
    }

    /**
     * Update settings
     */
    public function update_settings() {
        woocommerce_update_options($this->get_settings());
    }

    /**
     * Get settings for WooCommerce settings tab
     *
     * @return array
     */
    private function get_settings() {
        $settings = array(
            array(
                'title' => __('Price Display Settings', 'wc-extra-product-options'),
                'type'  => 'title',
                'id'    => 'wcepo_price_display_options',
            ),
            array(
                'title'    => __('From Text', 'wc-extra-product-options'),
                'desc'     => __('Text displayed before the minimum price (e.g., "From", "Starting at").', 'wc-extra-product-options'),
                'id'       => 'wcepo_from_text',
                'default'  => __('From', 'wc-extra-product-options'),
                'type'     => 'text',
                'desc_tip' => true,
            ),
            array(
                'title'   => __('Show "From" Text', 'wc-extra-product-options'),
                'desc'    => __('Display "From" text before the minimum price for variable products.', 'wc-extra-product-options'),
                'id'      => 'wcepo_show_from_text',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcepo_price_display_options',
            ),
            array(
                'title' => __('Handling Fee Settings', 'wc-extra-product-options'),
                'type'  => 'title',
                'id'    => 'wcepo_handling_fee_options',
            ),
            array(
                'title'    => __('Handling Fee Label', 'wc-extra-product-options'),
                'desc'     => __('Label displayed for the handling fee.', 'wc-extra-product-options'),
                'id'       => 'wcepo_handling_fee_label',
                'default'  => __('Handling Fee', 'wc-extra-product-options'),
                'type'     => 'text',
                'desc_tip' => true,
            ),
            array(
                'title'   => __('Include in Price Display', 'wc-extra-product-options'),
                'desc'    => __('Include handling fee in the displayed product price.', 'wc-extra-product-options'),
                'id'      => 'wcepo_include_handling_in_price',
                'default' => 'yes',
                'type'    => 'checkbox',
            ),
            array(
                'title'   => __('Show Fee Separately', 'wc-extra-product-options'),
                'desc'    => __('Show handling fee as a separate line item on the product page.', 'wc-extra-product-options'),
                'id'      => 'wcepo_show_handling_fee_separately',
                'default' => 'no',
                'type'    => 'checkbox',
            ),
            array(
                'type' => 'sectionend',
                'id'   => 'wcepo_handling_fee_options',
            ),
        );

        return $settings;
    }
}
