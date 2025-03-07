/**
 * Admin JavaScript for Autocomplete Orders
 */
jQuery(document).ready(function($) {
    // Track if settings have been changed
    var settingsChanged = false;
    
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
        
        // Fix for standard WooCommerce forms - prevent the unsaved changes warning when we're using AJAX
        $(window).on('beforeunload', function() {
            if (settingsChanged) {
                return true;
            }
        });
        
        // Make sure WooCommerce doesn't interfere with our AJAX saving
        $(document).off('change', '.woocommerce-save-button');
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
                    '<p><strong>🔍 Important: When to use payment method overrides</strong></p>' +
                    '<p>These settings let you override the default order status for specific payment methods, regardless of your Autocomplete Mode setting above.</p>' +
                    '<p><strong>For example:</strong></p>' +
                    '<ul>' +
                        '<li>If you want Cash on Delivery orders to stay as "On Hold" for manual review (even if your main setting completes all orders), set Cash on Delivery Default Status to "On Hold".</li>' +
                        '<li>If you want all payment methods to behave according to your main setting, leave these all as "Default".</li>' +
                    '</ul>' +
                    '<p><strong>Note:</strong> Some payment methods like Check Payments, Direct Bank Transfer, and Cash on Delivery usually set orders to "On Hold" by default. If you want these to be automatically completed, set them to "Completed" here.</p>' +
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
    
    // Prevent the standard form submission
    $('#mainform').on('submit', function(e) {
        if ($('#aco_section_title').length) {
            e.preventDefault();
            saveAllSettings();
            return false;
        }
    });
    
    // Initialize AJAX saving for toggles
    $(document).on('change', '.aco-ajax-checkbox', function() {
        var $checkbox = $(this);
        var settingId = $checkbox.attr('id');
        var value = $checkbox.prop('checked') ? 'yes' : 'no';
        
        // Mark settings as changed
        settingsChanged = true;
        
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
        
        // Mark settings as changed
        settingsChanged = true;
        
        // Save the current value for potential revert
        $select.data('previous-value', value);
        
        // Show saving status
        showStatusMessage(aco_params.messages.saving, 'info');
        
        // Save the setting via AJAX
        saveSetting(settingId, value);
    });
    
    // Function to save a setting via AJAX
    function saveSetting(settingId, value) {
        // Clear any previous save timers
        if (window.saveTimer) {
            clearTimeout(window.saveTimer);
        }
        
        // Add a visual indicator that saving is in progress
        $('.aco-status-message').addClass('saving');
        
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
                    
                    // Mark as saved
                    settingsChanged = false;
                    $('.aco-status-message').removeClass('saving');
                    
                    // If this was the autocomplete mode, update the UI
                    if (settingId === 'aco_autocomplete_mode') {
                        updateAutocompleteUI(value);
                    }
                } else {
                    showStatusMessage(response.data.message || aco_params.messages.error, 'error');
                    $('.aco-status-message').removeClass('saving');
                }
            },
            error: function() {
                showStatusMessage(aco_params.messages.error, 'error');
                $('.aco-status-message').removeClass('saving');
            }
        });
    }
    
    // Function to save all settings at once
    function saveAllSettings() {
        // Show saving message
        showStatusMessage(aco_params.messages.saving, 'info');
        $('.aco-status-message').addClass('saving');
        
        // Prevent double-clicking
        $('.woocommerce-save-button').prop('disabled', true);
        
        // Collect all settings
        var settings = {};
        
        // Collect checkbox values
        $('.aco-ajax-checkbox').each(function() {
            var $checkbox = $(this);
            settings[$checkbox.attr('id')] = $checkbox.prop('checked') ? 'yes' : 'no';
        });
        
        // Collect select values
        $('.aco-ajax-select').each(function() {
            var $select = $(this);
            settings[$select.attr('id')] = $select.val();
        });
        
        // Save all settings at once
        $.ajax({
            url: aco_params.ajax_url,
            type: 'POST',
            data: {
                action: 'aco_save_all_settings',
                nonce: aco_params.nonce,
                settings: settings
            },
            success: function(response) {
                if (response.success) {
                    // Get a random fun message
                    var funMessages = aco_params.messages.fun_success;
                    var randomMessage = funMessages[Math.floor(Math.random() * funMessages.length)];
                    
                    // Show success message with confetti effect
                    showStatusMessage(randomMessage, 'success');
                    
                    // Add a celebration animation
                    celebrateSuccess();
                    
                    settingsChanged = false;
                    $('.aco-status-message').removeClass('saving');
                } else {
                    showStatusMessage(response.data.message || 'Error saving settings', 'error');
                    $('.aco-status-message').removeClass('saving');
                }
                // Re-enable the button
                $('.woocommerce-save-button').prop('disabled', false);
            },
            error: function() {
                showStatusMessage('Error saving settings', 'error');
                $('.aco-status-message').removeClass('saving');
                // Re-enable the button
                $('.woocommerce-save-button').prop('disabled', false);
            }
        });
    }
    
    // Function to show status message
    function showStatusMessage(message, type) {
        var $statusMessage = $('.aco-status-message');
        $statusMessage.removeClass('success error info').addClass(type);
        $statusMessage.find('.message').text(message);
        $statusMessage.fadeIn();
        
        // Auto-hide success and info messages after 5 seconds (increased from 3)
        if (type === 'success' || type === 'info') {
            setTimeout(function() {
                $statusMessage.fadeOut();
            }, 5000);
        }
    }
    
    // Function for success celebration animation
    function celebrateSuccess() {
        // Create celebration container if it doesn't exist
        if ($('#aco-celebration').length === 0) {
            $('body').append('<div id="aco-celebration"></div>');
        }
        
        // Create confetti elements
        var colors = ['#7f54b3', '#46b450', '#ffba00', '#2271b1', '#dc3232'];
        var confettiCount = 50;
        var $celebration = $('#aco-celebration');
        
        $celebration.empty(); // Clear any previous confetti
        
        for (var i = 0; i < confettiCount; i++) {
            var $confetti = $('<div class="aco-confetti"></div>');
            var color = colors[Math.floor(Math.random() * colors.length)];
            var left = Math.random() * 100;
            var size = Math.random() * 10 + 5;
            var duration = Math.random() * 3 + 2;
            
            $confetti.css({
                'background-color': color,
                'left': left + '%',
                'width': size + 'px',
                'height': size + 'px',
                'animation-duration': duration + 's'
            });
            
            $celebration.append($confetti);
        }
        
        // Remove celebration after animation completes
        setTimeout(function() {
            $celebration.empty();
        }, 5000);
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
    
    // Keep the submit button but change its text and functionality
    $('p.submit button.woocommerce-save-button').text('Save All Settings').on('click', function(e) {
        e.preventDefault();
        saveAllSettings();
        return false;
    });
    
    // Add a reset button next to the original submit
    $('p.submit').append(
        '<button type="button" class="button button-secondary" id="aco-reset-settings" style="margin-left: 10px;">Reset to Defaults</button>'
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
            settingsChanged = false;
        }
    });
}); 