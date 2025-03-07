<?php
/**
 * Admin Class
 *
 * @package ACO
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ACO_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Add WooCommerce settings tab
        add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_tab'), 50);
        
        // Add settings to the tab
        add_action('woocommerce_settings_tabs_aco_settings', array($this, 'settings_tab'));
        
        // Save settings
        add_action('woocommerce_update_options_aco_settings', array($this, 'update_settings'));
        
        // Add admin scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // AJAX handler for saving settings
        add_action('wp_ajax_aco_save_setting', array($this, 'ajax_save_setting'));
    }
    
    /**
     * Add settings tab to WooCommerce settings
     *
     * @param array $settings_tabs
     * @return array
     */
    public function add_settings_tab($settings_tabs) {
        $settings_tabs['aco_settings'] = __('Autocomplete Orders', 'aco');
        return $settings_tabs;
    }
    
    /**
     * Settings tab content
     */
    public function settings_tab() {
        woocommerce_admin_fields($this->get_settings());
    }
    
    /**
     * Update settings
     */
    public function update_settings() {
        woocommerce_update_options($this->get_settings());
    }
    
    /**
     * AJAX handler to save individual settings
     */
    public function ajax_save_setting() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aco-admin-nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'aco')));
        }
        
        // Get setting details
        $setting_id = isset($_POST['setting_id']) ? sanitize_text_field($_POST['setting_id']) : '';
        $value = isset($_POST['value']) ? sanitize_text_field($_POST['value']) : '';
        
        // Validate the setting belongs to our plugin
        if (strpos($setting_id, 'aco_') !== 0) {
            wp_send_json_error(array('message' => __('Invalid setting ID', 'aco')));
        }
        
        // Update the option
        update_option($setting_id, $value);
        
        // Send success response
        wp_send_json_success(array(
            'message' => __('Setting saved successfully! 🎉', 'aco'),
            'setting_id' => $setting_id,
            'value' => $value
        ));
    }
    
    /**
     * Get settings array
     *
     * @return array
     */
    public function get_settings() {
        $settings = array(
            'section_title' => array(
                'name'     => __('Autocomplete Orders Settings', 'aco'),
                'type'     => 'title',
                'desc'     => __('Customize how and when your WooCommerce orders are automatically completed. Less manual work, more time for coffee! ☕', 'aco'),
                'id'       => 'aco_section_title'
            ),
            
            'autocomplete_mode' => array(
                'name'     => __('Autocomplete Mode', 'aco'),
                'desc_tip' => __('Choose which orders should be automatically completed after payment.', 'aco'),
                'id'       => 'aco_autocomplete_mode',
                'type'     => 'select',
                'class'    => 'wc-enhanced-select aco-ajax-select',
                'css'      => 'min-width: 350px;',
                'default'  => 'none',
                'options'  => array(
                    'none'              => __('None - No orders will be automatically completed', 'aco'),
                    'all'               => __('All Orders - All paid orders will be automatically completed', 'aco'),
                    'virtual'           => __('Virtual Orders - Orders with only virtual products will be automatically completed', 'aco'),
                    'virtual_downloadable' => __('Virtual & Downloadable Orders - Keep default WooCommerce behavior', 'aco'),
                ),
            ),
            
            'payment_gateways' => array(
                'name'     => __('Order Status Override by Payment Method', 'aco'),
                'desc_tip' => __('Override the default order status for specific payment methods.', 'aco'),
                'id'       => 'aco_payment_gateways_section',
                'type'     => 'title',
                'desc'     => __('These settings let you override how WooCommerce handles order statuses for specific payment methods. Only change these if you want different behavior than your Autocomplete Mode setting above.', 'aco'),
            ),
        );
        
        // Get available payment gateways
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        
        // Core payment gateways we want to show
        $core_gateways = array(
            'bacs',
            'cheque',
            'cod',
            'paypal',
            'stripe',
        );
        
        foreach ($payment_gateways as $gateway) {
            // Only include core gateways or filter by your preference
            if (in_array($gateway->id, $core_gateways)) {
                $settings[$gateway->id . '_status'] = array(
                    'name'     => sprintf(__('%s Default Status', 'aco'), $gateway->get_title()),
                    'desc_tip' => sprintf(__('Override the default status for %s payments.', 'aco'), $gateway->get_title()),
                    'id'       => 'aco_' . $gateway->id . '_status',
                    'type'     => 'select',
                    'class'    => 'wc-enhanced-select aco-ajax-select',
                    'css'      => 'min-width: 350px;',
                    'default'  => '',
                    'options'  => array(
                        ''            => __('Default - Use Autocomplete Mode setting', 'aco'),
                        'processing'  => __('Processing - Manual completion required', 'aco'),
                        'completed'   => __('Completed - Automatic completion', 'aco'),
                        'on-hold'     => __('On Hold - Manual review required', 'aco'),
                    ),
                );
            }
        }
        
        $settings['double_check'] = array(
            'name'     => __('Payment Verification', 'aco'),
            'desc'     => __('Wait 1 minute before completing the order to verify payment is successful (recommended for better reliability)', 'aco'),
            'id'       => 'aco_double_check',
            'type'     => 'checkbox',
            'default'  => 'yes',
            'class'    => 'aco-ajax-checkbox',
        );
        
        $settings['section_end'] = array(
            'type'     => 'sectionend',
            'id'       => 'aco_section_end'
        );
        
        return apply_filters('aco_settings', $settings);
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_scripts($hook) {
        $screen = get_current_screen();
        
        // Only enqueue on our settings page
        if ($hook == 'woocommerce_page_wc-settings' && isset($_GET['tab']) && $_GET['tab'] == 'aco_settings') {
            // Enqueue our custom CSS
            wp_enqueue_style('aco-admin-styles', ACO_URL . 'assets/css/admin.css', array(), ACO_VERSION);
            
            // Enqueue our custom JS
            wp_enqueue_script('aco-admin-scripts', ACO_URL . 'assets/js/admin.js', array('jquery'), ACO_VERSION, true);
            
            // Add some JS variables
            wp_localize_script('aco-admin-scripts', 'aco_params', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('aco-admin-nonce'),
                'messages' => array(
                    'saving'  => __('Saving...', 'aco'),
                    'saved'   => __('✓ Saved!', 'aco'),
                    'error'   => __('Error saving', 'aco'),
                    'confirm_all' => __('🔔 You\'re about to set ALL paid orders to be automatically completed. This is great for digital products but may not be ideal if you ship physical products. Continue?', 'aco'),
                )
            ));
        }
    }
}

// Initialize admin
new ACO_Admin(); 