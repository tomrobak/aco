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
        
        // Add status message container for AJAX notifications
        $('.aco-header').after(
            '<div class="aco-status-message" style="display: none;">' +
                '<span class="message"></span>' +
                '<span class="dashicons dashicons-dismiss close"></span>' +
            '</div>'
        );
        
        // Close status message when clicking the X
        $('.aco-status-message .close').on('click', function() {
            $('.aco-status-message').fadeOut();
        });
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
                '<div class="aco-info-box info">' +
                    '<p><strong>🔍 When should I use these payment method overrides?</strong></p>' +
                    '<p>You only need to change these settings if you want specific payment methods to behave differently than your main Autocomplete Mode setting.</p>' +
                    '<p>For example, you might want to automatically complete all orders (Autocomplete Mode = "All Orders") but keep Cash on Delivery orders as "On Hold" for manual review.</p>' +
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
        var $toggle = $('<label class="aco-toggle"><input type="checkbox" name="' + $checkbox.attr('name') + '" id="' + $checkbox.attr('id') + '" class="' + $checkbox.attr('class') + '" ' + ($checkbox.prop('checked') ? 'checked' : '') + '><span class="aco-toggle-slider"></span></label>');
        
        // Replace the original label with our new structure
        $label.replaceWith($toggle);
        $toggle.after('<span class="description">' + labelText + '</span>');
    });
    
    // Initialize AJAX saving for toggles
    $(document).on('change', '.aco-ajax-checkbox', function() {
        var $checkbox = $(this);
        var settingId = $checkbox.attr('id');
        var value = $checkbox.prop('checked') ? 'yes' : 'no';
        
        // Show saving status
        showStatusMessage(aco_params.messages.saving, 'info');
        
        // Save the setting via AJAX
        saveSetting(settingId, value);
    });
    
    // Initialize AJAX saving for selects
    $(document).on('change', '.aco-ajax-select', function() {
        var $select = $(this);
        var settingId = $select.attr('id');
        var value = $select.val();
        
        // For autocomplete_mode = all, show confirmation
        if (settingId === 'aco_autocomplete_mode' && value === 'all') {
            if (!confirm(aco_params.messages.confirm_all)) {
                // Revert selection
                $select.val($select.data('previous-value') || 'none').trigger('change.select2');
                return;
            }
        }
        
        // Save the current value for potential revert
        $select.data('previous-value', value);
        
        // Show saving status
        showStatusMessage(aco_params.messages.saving, 'info');
        
        // Save the setting via AJAX
        saveSetting(settingId, value);
    });
    
    // Function to save a setting via AJAX
    function saveSetting(settingId, value) {
        $.ajax({
            url: aco_params.ajax_url,
            type: 'POST',
            data: {
                action: 'aco_save_setting',
                nonce: aco_params.nonce,
                setting_id: settingId,
                value: value
            },
            success: function(response) {
                if (response.success) {
                    showStatusMessage(aco_params.messages.saved, 'success');
                    
                    // If this was the autocomplete mode, update the UI
                    if (settingId === 'aco_autocomplete_mode') {
                        updateAutocompleteUI(value);
                    }
                } else {
                    showStatusMessage(response.data.message || aco_params.messages.error, 'error');
                }
            },
            error: function() {
                showStatusMessage(aco_params.messages.error, 'error');
            }
        });
    }
    
    // Function to show status message
    function showStatusMessage(message, type) {
        var $statusMessage = $('.aco-status-message');
        $statusMessage.removeClass('success error info').addClass(type);
        $statusMessage.find('.message').text(message);
        $statusMessage.fadeIn();
        
        // Auto-hide success and info messages after 3 seconds
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                $statusMessage.fadeOut();
            }, 3000);
        }
    }
    
    // Function to update UI based on autocomplete mode
    function updateAutocompleteUI(mode) {
        // Add any special UI updates based on the selected mode
    }
    
    // Enhance select boxes
    if (typeof $.fn.selectWoo !== 'undefined') {
        $('.wc-enhanced-select').selectWoo({
            minimumResultsForSearch: 10
        });
    }
    
    // Hide the submit button since we're using AJAX
    $('p.submit').hide();
    
    // Add a reset button next to the original submit
    $('p.submit').before(
        '<div class="aco-action-buttons">' +
            '<button type="button" class="button button-secondary" id="aco-reset-settings">Reset to Defaults</button>' +
        '</div>'
    );
    
    // Handle reset button
    $('#aco-reset-settings').on('click', function() {
        if (confirm('This will reset all Autocomplete Orders settings to their defaults. Are you sure?')) {
            // Show resetting message
            showStatusMessage('Resetting settings...', 'info');
            
            // Reset each setting
            $('#aco_autocomplete_mode').val('none').trigger('change');
            $('#aco_double_check').prop('checked', true).trigger('change');
            
            // Reset payment gateway settings
            $('.aco-ajax-select').each(function() {
                if ($(this).attr('id') !== 'aco_autocomplete_mode') {
                    $(this).val('').trigger('change');
                }
            });
            
            showStatusMessage('All settings reset to defaults ✓', 'success');
        }
    });
}); 