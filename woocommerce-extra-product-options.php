<?php
/**
 * Plugin Name: WooCommerce Extra Product Options
 * Plugin URI: https://github.com/Moley123/WooCommerceExtraProductOptions
 * Description: Adds handling fees and displays lowest price with "From..." prefix for variable products in WooCommerce.
 * Version: 1.0.2
 * Author: Mark Lebrett
 * Author Website: https://marklebrett.co.uk
 * Author URI: https://github.com/Moley123
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wc-extra-product-options
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WCEPO_VERSION', '1.0.0');
define('WCEPO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WCEPO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WCEPO_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Declare HPOS (High-Performance Order Storage) compatibility
add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

/**
 * Main plugin class
 */
class WC_Extra_Product_Options {

    /**
     * Single instance of the class
     *
     * @var WC_Extra_Product_Options
     */
    private static $instance = null;

    /**
     * Get single instance of the class
     *
     * @return WC_Extra_Product_Options
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
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'check_woocommerce'));

        // Load plugin files
        add_action('plugins_loaded', array($this, 'load_plugin'), 20);

        // Register activation/deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Check if WooCommerce is active
     */
    public function check_woocommerce() {
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return false;
        }
        return true;
    }

    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php esc_html_e('WooCommerce Extra Product Options requires WooCommerce to be installed and active.', 'wc-extra-product-options'); ?></p>
        </div>
        <?php
    }

    /**
     * Load plugin files
     */
    public function load_plugin() {
        if (!$this->check_woocommerce()) {
            return;
        }

        // Load includes
        $this->includes();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Admin classes
        require_once WCEPO_PLUGIN_DIR . 'includes/class-wcepo-admin.php';
        require_once WCEPO_PLUGIN_DIR . 'includes/class-wcepo-settings.php';

        // Frontend classes
        require_once WCEPO_PLUGIN_DIR . 'includes/class-wcepo-frontend.php';
        require_once WCEPO_PLUGIN_DIR . 'includes/class-wcepo-price-display.php';

        // Initialize classes
        WCEPO_Admin::get_instance();
        WCEPO_Settings::get_instance();
        WCEPO_Frontend::get_instance();
        WCEPO_Price_Display::get_instance();
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $default_options = array(
            'handling_fee_label' => __('Handling Fee', 'wc-extra-product-options'),
            'from_text' => __('From', 'wc-extra-product-options'),
            'show_from_text' => 'yes',
            'include_handling_in_price' => 'yes',
        );

        foreach ($default_options as $key => $value) {
            if (get_option('wcepo_' . $key) === false) {
                add_option('wcepo_' . $key, $value);
            }
        }

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
}

// Initialize the plugin
function wcepo_init() {
    return WC_Extra_Product_Options::get_instance();
}

// Start the plugin
wcepo_init();
