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
        
        // AJAX handler for saving all settings at once
        add_action('wp_ajax_aco_save_all_settings', array($this, 'ajax_save_all_settings'));
        
        // AJAX handler for checking for updates
        add_action('wp_ajax_aco_check_updates', array($this, 'ajax_check_updates'));
        
        // Add the "Check for Updates" button to the plugin row
        add_filter('plugin_action_links_' . ACO_BASENAME, array($this, 'add_plugin_action_links'), 10, 1);
        
        // Add a button to the plugin update message
        add_action('in_plugin_update_message-' . ACO_BASENAME, array($this, 'add_update_message'), 10, 2);
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
     * AJAX handler to save all settings at once
     */
    public function ajax_save_all_settings() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aco-admin-nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'aco')));
        }
        
        // Get all settings
        $settings = isset($_POST['settings']) ? $_POST['settings'] : array();
        
        if (empty($settings) || !is_array($settings)) {
            wp_send_json_error(array('message' => __('No settings to save', 'aco')));
            return;
        }
        
        // Save each setting
        foreach ($settings as $setting_id => $value) {
            // Validate the setting belongs to our plugin
            if (strpos($setting_id, 'aco_') !== 0) {
                continue;
            }
            
            // Update the option
            update_option($setting_id, sanitize_text_field($value));
        }
        
        // Send success response
        wp_send_json_success(array(
            'message' => __('All settings saved successfully! 🎉', 'aco'),
            'count' => count($settings)
        ));
    }
    
    /**
     * AJAX handler to check for updates
     */
    public function ajax_check_updates() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'aco-admin-nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'aco')));
        }
        
        // Force WordPress to check for plugin updates
        $current = get_site_transient('update_plugins');
        set_site_transient('update_plugins', null);
        wp_update_plugins();
        $current = get_site_transient('update_plugins');
        
        // Check if our plugin has an update
        $update_available = false;
        $new_version = '';
        
        if (isset($current->response[ACO_BASENAME])) {
            $update_available = true;
            $new_version = $current->response[ACO_BASENAME]->new_version;
        }
        
        // Send response
        if ($update_available) {
            wp_send_json_success(array(
                'message' => sprintf(__('Update to version %s available! 🎉', 'aco'), $new_version),
                'version' => $new_version,
                'update_available' => true
            ));
        } else {
            wp_send_json_success(array(
                'message' => __('You have the latest version! 👍', 'aco'),
                'update_available' => false
            ));
        }
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
     * Add Check for Updates link to plugin actions
     * 
     * @param array $links Plugin action links
     * @return array Modified plugin action links
     */
    public function add_plugin_action_links($links) {
        // Check if any settings link already exists
        $has_settings = false;
        foreach ($links as $link) {
            // If any link text contains "Settings" or contains our settings URL, don't add another one
            if (strpos($link, '>Settings<') !== false || 
                strpos($link, 'page=wc-settings&tab=aco_settings') !== false) {
                $has_settings = true;
                break;
            }
        }
        
        // Only add settings link if none exists
        if (!$has_settings) {
            $settings_link = '<a href="' . admin_url('admin.php?page=wc-settings&tab=aco_settings') . '">' . __('Settings', 'aco') . '</a>';
            array_unshift($links, $settings_link);
        }
        
        // Add Update Check link with bold purple styling
        $update_link = '<a href="#" id="aco-check-updates" style="font-weight: bold; color: #7f54b3;">' . __('Update Check', 'aco') . '</a>';
        $links[] = $update_link;
        
        // Add script to handle the update check
        add_action('admin_footer', function() {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#aco-check-updates').on('click', function(e) {
                    e.preventDefault();
                    
                    // Add loading indicator
                    $(this).text('Checking...').css('opacity', '0.7');
                    
                    // Send AJAX request to check for updates
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'aco_check_updates',
                            nonce: '<?php echo wp_create_nonce('aco-admin-nonce'); ?>'
                        },
                        success: function(response) {
                            // Show notification
                            if (response.success) {
                                if (response.data.update_available) {
                                    // Show update available message
                                    alert('🚀 ' + response.data.message + '\n\nRefresh the page to see the update notification.');
                                    window.location.reload();
                                } else {
                                    // Show no update message
                                    alert('✅ ' + response.data.message);
                                }
                            } else {
                                // Show error message
                                alert('❌ Error checking for updates. Please try again later.');
                            }
                            
                            // Reset button text
                            $('#aco-check-updates').text('Update Check').css('opacity', '1');
                        },
                        error: function() {
                            // Show error message
                            alert('❌ Error checking for updates. Please try again later.');
                            
                            // Reset button text
                            $('#aco-check-updates').text('Update Check').css('opacity', '1');
                        }
                    });
                });
            });
            </script>
            <?php
        });
        
        return $links;
    }
    
    /**
     * Add a message to the plugin update notification
     * 
     * @param array $plugin_data Plugin data
     * @param object $response Update response data
     */
    public function add_update_message($plugin_data, $response) {
        echo '<br><span style="color: #7f54b3; font-weight: bold;">' . __('🚀 Woohoo! A new magical update is available!', 'aco') . '</span>';
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
            
            // Enqueue dashicons for the close button
            wp_enqueue_style('dashicons');
            
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
                    'fun_success' => array(
                        __('Woohoo! Settings saved with magical WooCommerce powers! 🧙‍♂️', 'aco'),
                        __('Boom! Your settings are now locked and loaded! 🚀', 'aco'),
                        __('High five! Your automation just got even more awesome! ✋', 'aco'),
                        __('Settings saved! Your orders will now complete themselves... like magic! ✨', 'aco'),
                        __('Success! Orders will now autocomplete faster than you can say "WooCommerce"! 🏎️', 'aco'),
                        __('Yippee! Your settings were saved and your orders are ready to party! 🎉', 'aco'),
                        __('Pow! Settings saved! Now your orders will practically complete themselves! 💪', 'aco')
                    )
                )
            ));
        }
    }
}

// Initialize admin
new ACO_Admin(); 