/**
 * WooCommerce Integration JavaScript for Keyless Auth
 *
 * Handles magic link toggle and AJAX submission on WooCommerce login forms
 *
 * @package Keyless Auth
 * @since 3.1.0
 */

(function() {
    'use strict';

    /**
     * Simple slide toggle with vanilla JS
     */
    function slideToggle(element, duration) {
        if (element.style.display === 'none' || !element.style.display) {
            element.style.display = 'block';
            let height = element.scrollHeight;
            element.style.height = '0';
            element.style.overflow = 'hidden';
            element.style.transition = 'height ' + duration + 'ms ease';

            setTimeout(function() {
                element.style.height = height + 'px';
            }, 10);

            setTimeout(function() {
                element.style.height = '';
                element.style.overflow = '';
                element.style.transition = '';
            }, duration);
        } else {
            element.style.height = element.scrollHeight + 'px';
            element.style.overflow = 'hidden';
            element.style.transition = 'height ' + duration + 'ms ease';

            setTimeout(function() {
                element.style.height = '0';
            }, 10);

            setTimeout(function() {
                element.style.display = 'none';
                element.style.height = '';
                element.style.overflow = '';
                element.style.transition = '';
            }, duration);
        }
    }

    /**
     * Toggle magic link form visibility
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Handle toggle links
        const toggleLinks = document.querySelectorAll('.chrmrtns-kla-wc-toggle-link');
        toggleLinks.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('data-target');
                const targetElement = document.getElementById(targetId);
                if (targetElement) {
                    slideToggle(targetElement, 300);
                    this.classList.toggle('active');

                    // Update link text based on state
                    if (this.classList.contains('active')) {
                        this.textContent = chrmrtns_kla_wc.close_form || 'Close magic link form';
                    } else {
                        this.textContent = chrmrtns_kla_wc.open_form || 'Or login with magic link instead';
                    }
                }
            });
        });

        /**
         * Handle magic link submission
         */
        const submitButtons = document.querySelectorAll('.chrmrtns-kla-wc-submit-magic');
        submitButtons.forEach(function(button) {
            button.addEventListener('click', async function(e) {
                e.preventDefault();

                const emailFieldId = this.getAttribute('data-email-field');
                const emailField = document.getElementById(emailFieldId);
                const email = emailField ? emailField.value : '';

                // Find the status message element
                const form = this.closest('.chrmrtns-kla-wc-magic-form');
                const statusMsg = form ? form.querySelector('.chrmrtns-kla-wc-magic-status') : null;

                // Validate email
                if (!email || !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    if (statusMsg) {
                        statusMsg.innerHTML = '<span style="color: #dc3232;">' + chrmrtns_kla_wc.error_invalid_email + '</span>';
                        statusMsg.style.display = 'block';
                    }
                    return;
                }

                // Disable button and show loading
                button.disabled = true;
                button.textContent = chrmrtns_kla_wc.sending;
                if (statusMsg) {
                    statusMsg.innerHTML = '';
                    statusMsg.style.display = 'none';
                }

                // Use API abstraction layer if available, otherwise fallback to AJAX
                if (window.KeylessAuthAPI && window.chrmrtnsKlaApiConfig) {
                    try {
                        const api = new window.KeylessAuthAPI(window.chrmrtnsKlaApiConfig);
                        const response = await api.requestLoginLink(email, window.location.href);

                        if (response.success) {
                            // Redirect to show success message
                            const currentUrl = window.location.href;
                            const separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
                            window.location.href = currentUrl + separator + 'chrmrtns_kla_wc_sent=1';
                        } else {
                            if (statusMsg) {
                                statusMsg.innerHTML = '<span style="color: #dc3232;">' + response.message + '</span>';
                                statusMsg.style.display = 'block';
                            }
                            button.disabled = false;
                            button.textContent = chrmrtns_kla_wc.send_link;
                        }
                    } catch (error) {
                        if (statusMsg) {
                            statusMsg.innerHTML = '<span style="color: #dc3232;">' + chrmrtns_kla_wc.error_occurred + '</span>';
                            statusMsg.style.display = 'block';
                        }
                        button.disabled = false;
                        button.textContent = chrmrtns_kla_wc.send_link;
                    }
                } else {
                    // Fallback to direct AJAX (for backward compatibility)
                    const formData = new FormData();
                    formData.append('action', 'chrmrtns_kla_wc_request_magic_link');
                    formData.append('email', email);
                    formData.append('nonce', chrmrtns_kla_ajax.nonce);
                    formData.append('redirect_to', window.location.href);

                    fetch(chrmrtns_kla_ajax.ajax_url, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin'
                    })
                    .then(function(response) {
                        return response.json();
                    })
                    .then(function(response) {
                        if (response.success) {
                            const currentUrl = window.location.href;
                            const separator = currentUrl.indexOf('?') !== -1 ? '&' : '?';
                            window.location.href = currentUrl + separator + 'chrmrtns_kla_wc_sent=1';
                        } else {
                            if (statusMsg) {
                                statusMsg.innerHTML = '<span style="color: #dc3232;">' + response.data + '</span>';
                                statusMsg.style.display = 'block';
                            }
                            button.disabled = false;
                            button.textContent = chrmrtns_kla_wc.send_link;
                        }
                    })
                    .catch(function() {
                        if (statusMsg) {
                            statusMsg.innerHTML = '<span style="color: #dc3232;">' + chrmrtns_kla_wc.error_occurred + '</span>';
                            statusMsg.style.display = 'block';
                        }
                        button.disabled = false;
                        button.textContent = chrmrtns_kla_wc.send_link;
                    });
                }
            });
        });
    });
})();
