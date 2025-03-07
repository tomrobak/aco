/**
 * Admin JavaScript for Autocomplete Orders
 */
jQuery(document).ready(function($) {
    // Add a custom header to the settings page
    if ($('.woocommerce_page_wc-settings').length && $('#aco_section_title').length) {
        $('#aco_section_title').closest('table').before(
            '<div class="aco-header">' +
                '<h2>🚀 Autocomplete Orders for WooCommerce</h2>' +
                '<p>Configure how and when your orders are automatically completed. Work smarter, not harder!</p>' +
            '</div>'
        );
    }
    
    // Add styling to sections
    $('.form-table').each(function() {
        $(this).wrap('<div class="aco-section"></div>');
    });
    
    // Add bootstrap-like classes to form elements
    $('.form-table tr').each(function() {
        $(this).addClass('aco-form-group');
    });
    
    // Add an info box about the autocomplete modes
    $('#aco_autocomplete_mode').closest('tr').after(
        '<tr>' +
            '<td colspan="2">' +
                '<div class="aco-info-box">' +
                    '<p><strong>How do the different modes work?</strong></p>' +
                    '<ul>' +
                        '<li><strong>None:</strong> No magic here - orders will need to be completed manually.</li>' +
                        '<li><strong>All Orders:</strong> All paid orders will be completed automatically. Perfect for digital stores!</li>' +
                        '<li><strong>Virtual Orders:</strong> Only orders containing virtual products will be completed automatically.</li>' +
                        '<li><strong>Virtual & Downloadable:</strong> Default WooCommerce behavior - only orders with virtual AND downloadable products will be completed automatically.</li>' +
                    '</ul>' +
                '</div>' +
            '</td>' +
        '</tr>'
    );
    
    // Add an info box about payment gateways
    $('#aco_payment_gateways_section').closest('tr').after(
        '<tr>' +
            '<td colspan="2">' +
                '<div class="aco-info-box warning">' +
                    '<p><strong>Note about payment gateways:</strong></p>' +
                    '<p>Some payment gateways like Check Payments, BACS, and COD typically set orders to "On Hold" or "Pending Payment" instead of "Processing". For these gateways, the autocomplete functionality will only work if you change their status to "Processing" or "Completed" here.</p>' +
                '</div>' +
            '</td>' +
        '</tr>'
    );
    
    // Add toggle effect for checkbox
    $('input[type="checkbox"]').each(function() {
        var $checkbox = $(this);
        var $label = $checkbox.closest('label');
        
        // Skip if already enhanced
        if ($checkbox.closest('.aco-toggle').length) {
            return;
        }
        
        // Get the text from the label
        var labelText = $label.text();
        
        // Remove the checkbox from the label
        $checkbox.detach();
        
        // Create the toggle
        var $toggle = $('<label class="aco-toggle"><input type="checkbox" name="' + $checkbox.attr('name') + '" id="' + $checkbox.attr('id') + '" ' + ($checkbox.prop('checked') ? 'checked' : '') + '><span class="aco-toggle-slider"></span></label>');
        
        // Replace the original label with our new structure
        $label.replaceWith($toggle);
        $toggle.after('<span class="description">' + labelText + '</span>');
    });
    
    // Enhance select boxes
    if (typeof $.fn.selectWoo !== 'undefined') {
        $('.wc-enhanced-select').selectWoo({
            minimumResultsForSearch: 10
        });
    }
    
    // Add confirmation before saving changes
    $('button[name="save"]').on('click', function(e) {
        // Prevent double click
        if ($(this).data('clicked')) {
            return;
        }
        
        var autocompleteMode = $('#aco_autocomplete_mode').val();
        if (autocompleteMode === 'all') {
            e.preventDefault();
            
            if (confirm('🔔 You\'re about to set ALL paid orders to be automatically completed. This is great for digital products but may not be ideal if you ship physical products. Continue?')) {
                $(this).data('clicked', true);
                $(this).trigger('click');
            }
        }
    });
}); 