/**
 * Frontend JavaScript for 2FA management
 * Handles user interactions with the [keyless-auth-2fa] shortcode
 *
 * @since 2.4.0
 */

// Test script loading BEFORE jQuery

jQuery(document).ready(function($) {
    'use strict';



    // Add flag to prevent accidental reloads
    window.chrmrtnsPreventReload = false;

    // Generate QR code if container exists
    const qrContainer = $('#chrmrtns-2fa-qrcode');

    if (qrContainer.length) {
        let totpUri = qrContainer.data('totp-uri');

        if (totpUri) {
            try {
                // Decode HTML entities (especially &amp; to &)
                const tempDiv = $('<div>').html(totpUri);
                totpUri = tempDiv.text();


                if (typeof QRCode !== 'undefined') {
                    qrContainer.empty();
                    // Wait a bit to ensure the library is fully loaded
                    setTimeout(function() {
                        try {
                                const qrcode = new QRCode(qrContainer[0], {
                                text: totpUri,
                                width: 200,
                                height: 200,
                                colorDark: '#000000',
                                colorLight: '#ffffff',
                                correctLevel: QRCode.CorrectLevel.M
                            });
                        } catch (qrError) {
                            console.error('QR code generation error:', qrError);
                            qrContainer.html('<div class="chrmrtns-qr-error">Unable to generate QR code. Please use manual entry below.</div>');
                        }
                    }, 100);
                } else {
                    // Try again after a short delay in case the library is loading
                    setTimeout(function() {
                        if (typeof QRCode !== 'undefined') {
                                qrContainer.empty();
                            try {
                                    const qrcode = new QRCode(qrContainer[0], {
                                    text: totpUri,
                                    width: 200,
                                    height: 200,
                                    colorDark: '#000000',
                                    colorLight: '#ffffff',
                                    correctLevel: QRCode.CorrectLevel.M
                                });
                            } catch (qrError) {
                                console.error('QR code generation error after delay:', qrError);
                                qrContainer.html('<div class="chrmrtns-qr-error">Unable to generate QR code. Please use manual entry below.</div>');
                            }
                        } else {
                                qrContainer.html('<div class="chrmrtns-qr-error">QRCode library not loaded. Please refresh the page.</div>');
                        }
                    }, 500);
                }
            } catch (error) {
                qrContainer.html('<div class="chrmrtns-qr-error">Unable to generate QR code. Please use manual entry below.</div>');
            }
        } else {
            qrContainer.html('<div class="chrmrtns-qr-error">No TOTP URI data found.</div>');
        }
    }


    // 2FA Setup Form
    $('#chrmrtns-2fa-setup-form').on('submit', function(e) {
        e.preventDefault();

        const $form = $(this);
        const $submitBtn = $form.find('.chrmrtns-2fa-setup-btn');
        const originalText = $submitBtn.text();

        // Disable form and show loading
        $submitBtn.prop('disabled', true).text('Setting up...');

        $.ajax({
            url: chrmrtns_2fa.ajax_url,
            type: 'POST',
            data: {
                action: 'chrmrtns_2fa_setup',
                secret: $form.find('[name="secret"]').val(),
                verification_code: $form.find('[name="verification_code"]').val(),
                chrmrtns_2fa_setup_nonce: $form.find('[name="chrmrtns_2fa_setup_nonce"]').val()
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showMessage(response.data.message, 'success');

                    // Show backup codes modal
                    if (response.data.backup_codes) {
                        showBackupCodesModal(response.data.backup_codes, true); // true = reload on close
                    } else {
                        // Only reload immediately if no backup codes to show
                        setTimeout(function() {
                            if (!window.chrmrtnsPreventReload) {
                                location.reload();
                            }
                        }, 2000);
                    }
                } else {
                    showMessage(response.data || chrmrtns_2fa.strings.error, 'error');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showMessage(chrmrtns_2fa.strings.error, 'error');
                $submitBtn.prop('disabled', false).text(originalText);
            }
        });
    });

    // Copy Secret Button
    $('.chrmrtns-copy-button').on('click', function() {
        const $button = $(this);
        const textToCopy = $button.data('copy');

        // Create temporary textarea for copying
        const $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(textToCopy).select();

        try {
            document.execCommand('copy');
            $button.text('Copied!').addClass('copied');

            setTimeout(function() {
                $button.text('Copy').removeClass('copied');
            }, 2000);
        } catch (err) {
            console.error('Failed to copy text: ', err);
        }

        $temp.remove();
    });

    // Show Backup Codes
    $('#chrmrtns-show-backup-codes').on('click', function() {
        const $codes = $('#chrmrtns-backup-codes');
        if ($codes.is(':visible')) {
            $codes.slideUp();
            $(this).text('View Backup Codes');
        } else {
            $codes.slideDown();
            $(this).text('Hide Backup Codes');
        }
    });


    // Generate Backup Codes

    // Try multiple approaches to ensure the handler gets bound
    // Approach 1: Direct binding
    $('#chrmrtns-generate-backup-codes').on('click', function() {
        handleGenerateBackupCodes.call(this);
    });

    // Approach 2: Delegated event binding (in case button is dynamically loaded)
    $(document).on('click', '#chrmrtns-generate-backup-codes', function() {
        handleGenerateBackupCodes.call(this);
    });

    // Function to handle backup code generation
    function handleGenerateBackupCodes() {
        if (!confirm('This will generate new backup codes and invalidate all existing codes. Continue?')) {
            return;
        }

        // Set flag to prevent any automatic reloads
        window.chrmrtnsPreventReload = true;

        const $button = $(this);
        const originalText = $button.text();

        $button.prop('disabled', true).text('Generating...');

        $.ajax({
            url: chrmrtns_2fa.ajax_url,
            type: 'POST',
            data: {
                action: 'chrmrtns_2fa_generate_backup_codes',
                nonce: chrmrtns_2fa.nonce
            },
            success: function(response) {
                if (response.success) {
                    // Don't show success message to avoid any interference
                    if (response.data.backup_codes) {
                        showBackupCodesModal(response.data.backup_codes, false); // false = don't auto-reload
                    }
                } else {
                    showMessage(response.data || chrmrtns_2fa.strings.error, 'error');
                }
            },
            error: function() {
                showMessage(chrmrtns_2fa.strings.error, 'error');
            },
            complete: function() {
                $button.prop('disabled', false).text(originalText);
            }
        });
    }

    // Disable 2FA
    $('#chrmrtns-disable-2fa').on('click', function() {
        if (!confirm(chrmrtns_2fa.strings.confirm_disable)) {
            return;
        }

        const $button = $(this);
        const originalText = $button.text();

        $button.prop('disabled', true).text('Disabling...');

        $.ajax({
            url: chrmrtns_2fa.ajax_url,
            type: 'POST',
            data: {
                action: 'chrmrtns_2fa_disable',
                nonce: chrmrtns_2fa.nonce
            },
            success: function(response) {
                if (response.success) {
                    showMessage(response.data.message, 'success');
                    setTimeout(function() {
                        if (!window.chrmrtnsPreventReload) {
                            location.reload();
                        }
                    }, 2000);
                } else {
                    showMessage(response.data || chrmrtns_2fa.strings.error, 'error');
                    $button.prop('disabled', false).text(originalText);
                }
            },
            error: function() {
                showMessage(chrmrtns_2fa.strings.error, 'error');
                $button.prop('disabled', false).text(originalText);
            }
        });
    });

    // Verification code input formatting
    $('#verification_code').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remove non-digits
        if (value.length > 6) {
            value = value.substring(0, 6);
        }
        $(this).val(value);
    });

    /**
     * Show message to user
     */
    function showMessage(message, type) {
        // Remove existing messages
        $('.chrmrtns-2fa-message').remove();

        const messageClass = type === 'success' ? 'chrmrtns-2fa-success' : 'chrmrtns-2fa-error';
        const messageHtml = '<div class="chrmrtns-2fa-message ' + messageClass + '">' +
            '<p>' + message + '</p>' +
            '</div>';

        $('#chrmrtns-2fa-container').prepend(messageHtml);

        // Auto-hide after 5 seconds
        setTimeout(function() {
            $('.chrmrtns-2fa-message').fadeOut();
        }, 5000);
    }

    /**
     * Show backup codes in a modal
     * @param {Array} codes - Array of backup codes
     * @param {boolean} reloadOnClose - Whether to reload page when modal is closed
     */
    function showBackupCodesModal(codes, reloadOnClose = false) {

        // Remove any existing modals first
        $('#chrmrtns-backup-modal').remove();

        // Create modal HTML
        const modalHtml = `
            <div id="chrmrtns-backup-modal" class="chrmrtns-modal-overlay">
                <div class="chrmrtns-modal-content">
                    <div class="chrmrtns-modal-header">
                        <h3>Your Backup Codes</h3>
                        <button type="button" class="chrmrtns-modal-close">&times;</button>
                    </div>
                    <div class="chrmrtns-modal-body">
                        <div class="chrmrtns-backup-warning">
                            <p><strong>Important:</strong> Save these backup codes in a safe place. Each code can only be used once.</p>
                            <p>You can use these codes to access your account if you lose your authenticator app.</p>
                        </div>
                        <div class="chrmrtns-backup-codes-grid">
                            ${codes.map(code => `<div class="chrmrtns-backup-code">${code}</div>`).join('')}
                        </div>
                        <div class="chrmrtns-modal-actions">
                            <button type="button" class="button" id="chrmrtns-copy-all-codes">Copy All Codes</button>
                            <button type="button" class="button" id="chrmrtns-download-codes">Download as Text</button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        $('body').append(modalHtml);

        // Handle modal close - close button
        $('.chrmrtns-modal-close').on('click', function(e) {
            e.preventDefault();
            $('#chrmrtns-backup-modal').remove();
            // Reload page if requested (e.g., after initial 2FA setup)
            if (reloadOnClose) {
                location.reload();
            }
        });

        // Handle modal close - clicking overlay background
        $('.chrmrtns-modal-overlay').on('click', function(e) {
            if (e.target === this) { // Only close when clicking the overlay itself, not child elements
                $('#chrmrtns-backup-modal').remove();
                // Reload page if requested (e.g., after initial 2FA setup)
                if (reloadOnClose) {
                    location.reload();
                }
            }
        });

        // Handle copy all codes
        $('#chrmrtns-copy-all-codes').on('click', function() {
            const allCodes = codes.join('\n');
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(allCodes).select();

            try {
                document.execCommand('copy');
                $(this).text('Copied!');
                setTimeout(() => $(this).text('Copy All Codes'), 2000);
            } catch (err) {
                console.error('Failed to copy codes: ', err);
            }

            $temp.remove();
        });

        // Handle download codes
        $('#chrmrtns-download-codes').on('click', function() {
            const content = `Two-Factor Authentication Backup Codes\n` +
                          `Generated: ${new Date().toLocaleDateString()}\n` +
                          `Site: ${window.location.hostname}\n\n` +
                          `Keep these codes safe! Each can only be used once.\n\n` +
                          codes.join('\n');

            const blob = new Blob([content], { type: 'text/plain' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'backup-codes.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        });

        // Prevent closing modal accidentally
        $(document).on('keydown.chrmrtns-modal', function(e) {
            if (e.key === 'Escape') {
                $('#chrmrtns-backup-modal').remove();
                $(document).off('keydown.chrmrtns-modal');
            }
        });
    }
});