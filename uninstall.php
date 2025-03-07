<?php
/**
 * Uninstall Autocomplete Orders
 *
 * @package ACO
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete options
delete_option('aco_autocomplete_mode');
delete_option('aco_double_check');

// Get available payment gateways
if (class_exists('WooCommerce')) {
    $payment_gateways = WC()->payment_gateways->payment_gateways();
    
    // Core payment gateways options to clean
    $core_gateways = array(
        'bacs',
        'cheque',
        'cod',
        'paypal',
        'stripe',
    );
    
    foreach ($core_gateways as $gateway) {
        delete_option('aco_' . $gateway . '_status');
    }
} 