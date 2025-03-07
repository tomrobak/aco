<?php
/**
 * Plugin Name: Autocomplete Orders for WooCommerce
 * Plugin URI: https://wplove.co/community/space/plugins-themes/home
 * Description: Supercharge your WooCommerce store by automatically completing orders based on your preferences. No more manual order processing - let the magic happen!
 * Version: 1.2.3
 * Author: wplove.co
 * Author URI: https://wplove.co
 * Text Domain: aco
 * Domain Path: /languages
 * Requires at least: 6.7
 * Requires PHP: 8.0
 * WC requires at least: 8.0.0
 * 
 * @package ACO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ACO_VERSION', '1.2.3');
define('ACO_FILE', __FILE__);
define('ACO_PATH', plugin_dir_path(__FILE__));
define('ACO_URL', plugin_dir_url(__FILE__));
define('ACO_BASENAME', plugin_basename(__FILE__));

/**
 * Check if WooCommerce is active
 */
if (!function_exists('aco_is_woocommerce_active')) {
    function aco_is_woocommerce_active() {
        $active_plugins = (array) get_option('active_plugins', array());
        
        if (is_multisite()) {
            $active_plugins = array_merge($active_plugins, get_site_option('active_sitewide_plugins', array()));
        }
        
        return in_array('woocommerce/woocommerce.php', $active_plugins) || array_key_exists('woocommerce/woocommerce.php', $active_plugins);
    }
}

// Main plugin class
final class AutocompleteOrders {
    
    /**
     * Singleton instance
     *
     * @var AutocompleteOrders
     */
    private static $instance = null;
    
    /**
     * Main AutocompleteOrders Instance
     * 
     * Ensures only one instance of AutocompleteOrders is loaded or can be loaded.
     * 
     * @return AutocompleteOrders - Main instance
     */
    public static function instance() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
        $this->includes();
    }
    
    /**
     * Hook into actions and filters
     */
    private function init_hooks() {
        // Check if WooCommerce is active
        add_action('admin_init', array($this, 'check_woocommerce_active'));
        
        // Load plugin text domain
        add_action('init', array($this, 'load_plugin_textdomain'));
        
        // Add settings link to plugins page
        add_filter('plugin_action_links_' . ACO_BASENAME, array($this, 'add_plugin_action_links'));
    }
    
    /**
     * Include required files
     */
    private function includes() {
        // Admin
        require_once ACO_PATH . 'includes/class-aco-admin.php';
        
        // Core functionality
        require_once ACO_PATH . 'includes/class-aco-core.php';
        
        // GitHub Updater
        require_once ACO_PATH . 'includes/class-aco-updater.php';
        
        // Initialize GitHub updater
        if (is_admin()) {
            new ACO_Updater(ACO_FILE, 'tomrobak', 'aco');
        }
    }
    
    /**
     * Check if WooCommerce is active, display notice if not
     */
    public function check_woocommerce_active() {
        if (!aco_is_woocommerce_active()) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            deactivate_plugins(ACO_BASENAME);
            if (isset($_GET['activate'])) {
                unset($_GET['activate']);
            }
        }
    }
    
    /**
     * Display notice if WooCommerce is not active
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e('🛒 Oops! Autocomplete Orders needs WooCommerce to work its magic. Please install and activate WooCommerce first, then we can get this party started! 🎉', 'aco'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain('aco', false, dirname(ACO_BASENAME) . '/languages');
    }
    
    /**
     * Add plugin action links
     * 
     * @param array $links
     * @return array
     */
    public function add_plugin_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=wc-settings&tab=aco_settings') . '">' . __('Settings', 'aco') . '</a>',
        );
        
        return array_merge($plugin_links, $links);
    }
}

/**
 * Returns the main instance of AutocompleteOrders
 * 
 * @return AutocompleteOrders
 */
function ACO() {
    return AutocompleteOrders::instance();
}

// Let's get this party started!
if (aco_is_woocommerce_active()) {
    add_action('plugins_loaded', 'ACO');
} 