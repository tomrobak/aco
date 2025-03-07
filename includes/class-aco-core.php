<?php
/**
 * Core Class
 *
 * @package ACO
 * @since 1.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class ACO_Core {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Hook into payment complete action
        add_action('woocommerce_payment_complete', array($this, 'maybe_autocomplete_order'), 10, 1);
        
        // Hook into order status changes for payment gateways
        add_action('woocommerce_order_status_changed', array($this, 'maybe_change_order_status'), 10, 4);
        
        // Schedule delayed completion check
        add_action('aco_delayed_order_complete', array($this, 'process_delayed_order_complete'), 10, 1);
    }
    
    /**
     * Maybe autocomplete order when payment is complete
     *
     * @param int $order_id
     */
    public function maybe_autocomplete_order($order_id) {
        // Get the order
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // If order is already completed or failed, don't do anything
        if ($order->get_status() === 'completed' || $order->get_status() === 'failed') {
            return;
        }
        
        // Get the autocomplete mode
        $autocomplete_mode = get_option('aco_autocomplete_mode', 'none');
        
        // If mode is none, don't do anything
        if ($autocomplete_mode === 'none') {
            return;
        }
        
        // If double check is enabled, schedule a delayed completion
        if (get_option('aco_double_check', 'yes') === 'yes') {
            $this->schedule_delayed_completion($order_id);
            return;
        }
        
        // Otherwise, process immediately
        $this->complete_order_if_applicable($order);
    }
    
    /**
     * Schedule a delayed order completion check
     *
     * @param int $order_id
     */
    private function schedule_delayed_completion($order_id) {
        // Schedule the event to run after 1 minute
        if (!wp_next_scheduled('aco_delayed_order_complete', array($order_id))) {
            wp_schedule_single_event(time() + 60, 'aco_delayed_order_complete', array($order_id));
            
            // Add order note
            $order = wc_get_order($order_id);
            if ($order) {
                $order->add_order_note(__('🕒 Autocomplete Orders: Scheduled to check payment status in 1 minute. Hang tight!', 'aco'));
            }
        }
    }
    
    /**
     * Process delayed order completion
     *
     * @param int $order_id
     */
    public function process_delayed_order_complete($order_id) {
        // Get the order
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return;
        }
        
        // If order is already completed or failed, don't do anything
        if ($order->get_status() === 'completed' || $order->get_status() === 'failed') {
            return;
        }
        
        // Check if payment status is still good
        if (!$order->is_paid()) {
            $order->add_order_note(__('⚠️ Autocomplete Orders: Order not marked as paid after 1 minute check. No automatic status change applied.', 'aco'));
            return;
        }
        
        // Process the order completion
        $this->complete_order_if_applicable($order);
    }
    
    /**
     * Complete order if applicable based on settings
     *
     * @param WC_Order $order
     */
    private function complete_order_if_applicable($order) {
        // Get the autocomplete mode
        $autocomplete_mode = get_option('aco_autocomplete_mode', 'none');
        
        switch ($autocomplete_mode) {
            case 'all':
                // Complete all orders
                $this->complete_order($order);
                break;
                
            case 'virtual':
                // Only complete if all products are virtual
                if ($this->order_has_only_virtual_products($order)) {
                    $this->complete_order($order);
                }
                break;
                
            case 'virtual_downloadable':
                // Let WooCommerce handle virtual and downloadable products
                // We don't need to do anything here
                break;
        }
    }
    
    /**
     * Check if the order contains only virtual products
     *
     * @param WC_Order $order
     * @return bool
     */
    private function order_has_only_virtual_products($order) {
        $only_virtual = true;
        
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            
            if ($product && !$product->is_virtual()) {
                $only_virtual = false;
                break;
            }
        }
        
        return $only_virtual;
    }
    
    /**
     * Complete the order
     *
     * @param WC_Order $order
     */
    private function complete_order($order) {
        // Change order status to completed
        $order->update_status('completed', __('🎉 Order automatically completed by Autocomplete Orders plugin. Magic! ✨', 'aco'));
    }
    
    /**
     * Maybe change order status based on payment gateway settings
     *
     * @param int $order_id
     * @param string $from_status
     * @param string $to_status
     * @param WC_Order $order
     */
    public function maybe_change_order_status($order_id, $from_status, $to_status, $order) {
        // Only proceed if changing to processing or on-hold status
        if (!in_array($to_status, array('processing', 'on-hold'))) {
            return;
        }
        
        // Get payment method
        $payment_method = $order->get_payment_method();
        
        if (!$payment_method) {
            return;
        }
        
        // Check if we have a custom status for this payment method
        $custom_status = get_option('aco_' . $payment_method . '_status', '');
        
        // If no custom status, don't do anything
        if (empty($custom_status)) {
            return;
        }
        
        // If already the custom status, don't do anything
        if ($to_status === $custom_status) {
            return;
        }
        
        // Update to custom status
        $order->update_status(
            $custom_status,
            sprintf(__('🔄 Order status changed to %s by Autocomplete Orders plugin based on payment method.', 'aco'), wc_get_order_status_name($custom_status))
        );
    }
}

// Initialize core
new ACO_Core(); 