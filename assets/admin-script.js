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

    // Color picker synchronization for email templates
    $('input[type="color"][id$="_picker"]').on('change input', function() {
        var color = $(this).val();
        var textInput = $(this).siblings('input[type="text"]');
        textInput.val(color);
    });
    
    // Text input to color picker synchronization
    $('input[id$="_text"]').on('input', function() {
        var colorValue = $(this).val();
        var colorPicker = $(this).siblings('input[type="color"]');
        
        // Only update color picker if it's a valid hex color
        if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(colorValue)) {
            colorPicker.val(colorValue);
        }
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