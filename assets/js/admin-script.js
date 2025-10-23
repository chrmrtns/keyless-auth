/**
 * Keyless Auth Admin JavaScript
 * @since 2.0.9
 */

jQuery(document).ready(function($) {
    // Mail logger functionality
    $('.chrmrtns-kla-mail-expand-btn').click(function() {
        var row = $(this).data('row');
        $('#chrmrtns-kla-mail-content-' + row).toggle();
        $(this).text($(this).text() === 'View' ? 'Hide' : 'View');
    });

    // Store initial color values for preview updates
    var initialColors = {
        button_color: $('#chrmrtns_kla_button_color_text').val() || '#007bff',
        button_hover_color: $('#chrmrtns_kla_button_hover_color_text').val() || '#0056b3',
        button_text_color: $('#chrmrtns_kla_button_text_color_text').val() || '#ffffff',
        button_hover_text_color: $('#chrmrtns_kla_button_hover_text_color_text').val() || '#ffffff',
        link_color: $('#chrmrtns_kla_link_color_text').val() || '#007bff',
        link_hover_color: $('#chrmrtns_kla_link_hover_color_text').val() || '#0056b3'
    };

    // Function to update all preview iframes with new colors
    function updatePreviewColors() {
        var newColors = {
            button_color: $('#chrmrtns_kla_button_color_text').val() || '#007bff',
            button_hover_color: $('#chrmrtns_kla_button_hover_color_text').val() || '#0056b3',
            button_text_color: $('#chrmrtns_kla_button_text_color_text').val() || '#ffffff',
            button_hover_text_color: $('#chrmrtns_kla_button_hover_text_color_text').val() || '#ffffff',
            link_color: $('#chrmrtns_kla_link_color_text').val() || '#007bff',
            link_hover_color: $('#chrmrtns_kla_link_hover_color_text').val() || '#0056b3'
        };

        // Update each preview iframe
        $('.template-preview-iframe').each(function() {
            var $iframe = $(this);
            var srcdoc = $iframe.attr('srcdoc');

            if (srcdoc) {
                // Replace old color values with new ones (case-insensitive for CSS)
                srcdoc = srcdoc.replace(new RegExp(initialColors.button_color, 'gi'), newColors.button_color);
                srcdoc = srcdoc.replace(new RegExp(initialColors.button_hover_color, 'gi'), newColors.button_hover_color);
                srcdoc = srcdoc.replace(new RegExp(initialColors.button_text_color, 'gi'), newColors.button_text_color);
                srcdoc = srcdoc.replace(new RegExp(initialColors.button_hover_text_color, 'gi'), newColors.button_hover_text_color);
                srcdoc = srcdoc.replace(new RegExp(initialColors.link_color, 'gi'), newColors.link_color);
                srcdoc = srcdoc.replace(new RegExp(initialColors.link_hover_color, 'gi'), newColors.link_hover_color);

                // Update iframe
                $iframe.attr('srcdoc', srcdoc);
            }
        });

        // Update initial colors for next change
        initialColors = newColors;
    }

    // Color picker synchronization for email templates
    $('input[type="color"][id$="_picker"]').on('change input', function() {
        var color = $(this).val();
        var textInput = $(this).siblings('input[type="text"]');
        textInput.val(color);
        updatePreviewColors();
    });

    // Text input to color picker synchronization
    $('input[id$="_text"]').on('input', function() {
        var colorValue = $(this).val();
        var colorPicker = $(this).siblings('input[type="color"]');

        // Only update color picker if it's a valid hex color
        if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(colorValue)) {
            colorPicker.val(colorValue);
        }
        updatePreviewColors();
    });
    
    // Show/hide custom template editor based on selection
    $('input[name="chrmrtns_kla_email_template"]').change(function() {
        if ($(this).val() === 'custom') {
            $('#chrmrtns_kla_custom_template_section').show();
        } else {
            $('#chrmrtns_kla_custom_template_section').hide();
        }
    });
    
    // Trigger initial show/hide for custom template  
    var currentTemplate = $('input[name="chrmrtns_kla_email_template"]:checked').val();
    if (currentTemplate === 'custom') {
        $('#chrmrtns_kla_custom_template_section').show();
    } else {
        $('#chrmrtns_kla_custom_template_section').hide();
    }

    // SMTP settings functionality
    function toggleCredentialFields() {
        var storageMethod = $('input[name="chrmrtns_kla_smtp_credential_storage"]:checked').val();
        
        if (storageMethod === 'config') {
            $('#chrmrtns_kla_username_row, #chrmrtns_kla_password_row').hide();
            $('.chrmrtns-kla-config-notice').show();
        } else {
            $('#chrmrtns_kla_username_row, #chrmrtns_kla_password_row').show();
            $('.chrmrtns-kla-config-notice').hide();
        }
    }

    // Initialize SMTP credential storage toggle
    if ($('input[name="chrmrtns_kla_smtp_credential_storage"]').length) {
        toggleCredentialFields();
        $('input[name="chrmrtns_kla_smtp_credential_storage"]').on('change', toggleCredentialFields);
    }

    // Toggle custom from name field
    $('#chrmrtns_kla_force_from_name').on('change', function() {
        $('#chrmrtns_kla_custom_from_name_field').toggle(this.checked);
    }).trigger('change');

    // Update port when encryption changes
    $('#chrmrtns_kla_smtp_encryption').change(function() {
        var encryption = $(this).val();
        var port = $('#chrmrtns_kla_smtp_port');
        if (encryption === 'ssl' && port.val() === '') {
            port.val('465');
        } else if (encryption === 'tls' && port.val() === '') {
            port.val('587');
        } else if (encryption === 'none' && port.val() === '') {
            port.val('25');
        }
    });

    // Credential storage toggle
    function handleCredentialStorage() {
        var storage = $('input[name="chrmrtns_kla_smtp_settings[credential_storage]"]:checked').val();
        var instructions = $('#wp-config-instructions');
        var usernameField = $('input[name="chrmrtns_kla_smtp_settings[smtp_username]"]');
        var passwordField = $('input[name="chrmrtns_kla_smtp_settings[smtp_password]"]');
        
        if (storage === 'wp_config') {
            instructions.css('display', 'block');
            // Check if constants are defined (indicated by disabled fields from PHP)
            if (usernameField.prop('disabled')) {
                usernameField.closest('tr').find('.description').html('<span style="color: green;">✓ Defined in wp-config.php</span>');
                passwordField.closest('tr').find('.description').html('<span style="color: green;">✓ Defined in wp-config.php</span>');
            }
        } else {
            instructions.css('display', 'none');
            usernameField.prop('disabled', false);
            passwordField.prop('disabled', false);
        }
    }
    
    // Bind change event
    $('input[name="chrmrtns_kla_smtp_settings[credential_storage]"]').on('change', handleCredentialStorage);
    
    // Check on page load
    handleCredentialStorage();

    // 2FA Settings functionality
    // Show/hide 2FA settings based on enable checkbox
    $('#chrmrtns_kla_2fa_enabled').on('change', function() {
        var settingsDiv = $('#chrmrtns-2fa-settings');
        if ($(this).is(':checked')) {
            settingsDiv.slideDown();
        } else {
            settingsDiv.slideUp();
        }
    });

    // Test email functionality
    $('#chrmrtns_kla_smtp_send_test').click(function(e) {
        e.preventDefault();
        
        var button = $(this);
        button.prop('disabled', true).val('Sending...');
        
        var data = {
            action: 'chrmrtns_kla_send_test_email',
            email: $('#chrmrtns_kla_smtp_test_email').val(),
            nonce: chrmrtns_kla_ajax.nonce
        };
        
        $.post(ajaxurl, data, function(response) {
            alert(response.data);
            button.prop('disabled', false).val('Send Test Email');
        }).fail(function() {
            alert('Error sending test email');
            button.prop('disabled', false).val('Send Test Email');
        });
    });
});

// Mail logs view content functions (global scope for onclick handlers)
function chrmrtnsShowEmailContent(logId) {
    var contentDiv = document.getElementById('chrmrtns_email_content_' + logId);
    if (contentDiv) {
        contentDiv.style.display = 'block';
    }
}

function chrmrtnsHideEmailContent(logId) {
    var contentDiv = document.getElementById('chrmrtns_email_content_' + logId);
    if (contentDiv) {
        contentDiv.style.display = 'none';
    }
}

// 2FA Admin functions (global scope for onclick handlers)
function chrmrtnsDisable2FA(userId, username) {
    if (!confirm('Are you sure you want to disable 2FA for user "' + username + '"? This will make their account less secure.')) {
        return;
    }

    var data = {
        action: 'chrmrtns_kla_admin_disable_2fa',
        user_id: userId,
        nonce: chrmrtns_kla_ajax.nonce
    };

    jQuery.post(ajaxurl, data, function(response) {
        if (response.success) {
            alert('2FA has been disabled for ' + username);
            location.reload(); // Refresh page to update user list
        } else {
            alert('Error: ' + (response.data || 'Failed to disable 2FA'));
        }
    }).fail(function() {
        alert('Network error occurred. Please try again.');
    });
}