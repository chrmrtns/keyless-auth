/**
 * Keyless Auth API Abstraction Layer
 *
 * Provides a unified interface for making login requests via AJAX or REST API.
 * Automatically detects which method is enabled and falls back gracefully.
 *
 * @package Keyless Auth
 * @since 3.3.0
 */

(function(window) {
    'use strict';

    /**
     * KeylessAuthAPI class
     *
     * Handles authentication requests using either REST API or AJAX
     */
    class KeylessAuthAPI {
        /**
         * Constructor
         *
         * @param {Object} config Configuration object with ajax_url, nonce, rest_url, rest_nonce, use_rest
         */
        constructor(config) {
            this.config = {
                ajax_url: config.ajax_url || '',
                ajax_nonce: config.ajax_nonce || '',
                rest_url: config.rest_url || '',
                rest_nonce: config.rest_nonce || '',
                use_rest: config.use_rest || false
            };
        }

        /**
         * Request a magic login link
         *
         * @param {string} emailOrUsername - Email address or username
         * @param {string} redirectUrl - Optional redirect URL after login
         * @returns {Promise} Promise that resolves with response data
         */
        async requestLoginLink(emailOrUsername, redirectUrl = '') {
            if (this.config.use_rest) {
                return this.requestViaREST(emailOrUsername, redirectUrl);
            } else {
                return this.requestViaAJAX(emailOrUsername, redirectUrl);
            }
        }

        /**
         * Request magic link via REST API
         *
         * @param {string} emailOrUsername
         * @param {string} redirectUrl
         * @returns {Promise}
         */
        async requestViaREST(emailOrUsername, redirectUrl) {
            try {
                const response = await fetch(this.config.rest_url + '/request-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': this.config.rest_nonce
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({
                        email_or_username: emailOrUsername,
                        redirect_url: redirectUrl
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    return {
                        success: true,
                        message: data.message || 'Magic login link sent!',
                        data: data.data || {}
                    };
                } else {
                    // REST API error response
                    return {
                        success: false,
                        message: data.message || 'An error occurred',
                        code: data.code || 'unknown_error'
                    };
                }
            } catch (error) {
                // Network error or JSON parse error
                return {
                    success: false,
                    message: 'Network error. Please try again.',
                    code: 'network_error'
                };
            }
        }

        /**
         * Request magic link via AJAX (backward compatibility)
         *
         * @param {string} emailOrUsername
         * @param {string} redirectUrl
         * @returns {Promise}
         */
        async requestViaAJAX(emailOrUsername, redirectUrl) {
            try {
                const formData = new FormData();
                formData.append('action', 'chrmrtns_kla_wc_request_magic_link');
                formData.append('email', emailOrUsername);
                formData.append('nonce', this.config.ajax_nonce);

                if (redirectUrl) {
                    formData.append('redirect_to', redirectUrl);
                }

                const response = await fetch(this.config.ajax_url, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });

                const data = await response.json();

                if (data.success) {
                    return {
                        success: true,
                        message: data.data || 'Magic login link sent!',
                        data: {}
                    };
                } else {
                    return {
                        success: false,
                        message: data.data || 'An error occurred',
                        code: 'ajax_error'
                    };
                }
            } catch (error) {
                return {
                    success: false,
                    message: 'Network error. Please try again.',
                    code: 'network_error'
                };
            }
        }

        /**
         * Check if REST API is enabled
         *
         * @returns {boolean}
         */
        isRestEnabled() {
            return this.config.use_rest;
        }

        /**
         * Get current API method
         *
         * @returns {string} 'rest' or 'ajax'
         */
        getApiMethod() {
            return this.config.use_rest ? 'rest' : 'ajax';
        }
    }

    // Expose to global scope
    window.KeylessAuthAPI = KeylessAuthAPI;

})(window);
