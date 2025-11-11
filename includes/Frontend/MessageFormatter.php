<?php
/**
 * Message Formatter Class
 *
 * Handles formatting and display of user-facing messages in Keyless Auth plugin.
 *
 * @package Keyless_Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Frontend;

/**
 * MessageFormatter class
 *
 * Provides methods to format success messages, error messages, and login errors
 * with consistent HTML structure and styling.
 */
class MessageFormatter {

    /**
     * Format success message with appropriate HTML wrapper
     *
     * @param string $message Success message text.
     * @return string Formatted HTML
     */
    public static function formatSuccessMessage($message) {
        return '<div class="chrmrtns-success-box chrmrtns-message-box">' .
               '<p>' . wp_kses_post($message) . '</p>' .
               '</div>';
    }

    /**
     * Format error message with appropriate HTML wrapper
     *
     * @param string $message Error message text.
     * @return string Formatted HTML
     */
    public static function formatErrorMessage($message) {
        return '<div class="chrmrtns-error-box chrmrtns-message-box">' .
               '<p>' . wp_kses_post($message) . '</p>' .
               '</div>';
    }

    /**
     * Format "currently logged in" message
     *
     * @param \WP_User $user Current user object.
     * @param string   $logout_url Logout URL.
     * @return string Formatted HTML
     */
    public static function formatLoggedInMessage($user, $logout_url) {
        $message = sprintf(
            // translators: %1$s: Display name, %2$s: Email, %3$s: Logout link.
            __('You are currently logged in as %1$s (%2$s). %3$s', 'keyless-auth'),
            '<strong>' . esc_html($user->display_name) . '</strong>',
            '<strong>' . esc_html($user->user_email) . '</strong>',
            '<a href="' . esc_url($logout_url) . '" title="' . esc_html__('Log out of this account', 'keyless-auth') . '">' .
            esc_html__('Log out', 'keyless-auth') . ' &raquo;</a>'
        );

        return self::formatSuccessMessage($message);
    }

    /**
     * Get user-friendly error message from WordPress login error codes
     *
     * @param string $error_code  Error code from login redirect.
     * @param string $login_status Login status parameter.
     * @return string Human-readable error message or empty string
     */
    public static function getLoginErrorMessage($error_code, $login_status) {
        // Handle common WordPress login error codes
        $error_messages = array(
            // Standard wp-login.php error codes
            'invalid_username'          => __('Invalid username or email address.', 'keyless-auth'),
            'incorrect_password'        => __('The password you entered is incorrect.', 'keyless-auth'),
            'invalidcombo'              => __('Invalid username or password.', 'keyless-auth'),
            'empty_username'            => __('Please enter your username or email address.', 'keyless-auth'),
            'empty_password'            => __('Please enter your password.', 'keyless-auth'),
            'invalid_email'             => __('Invalid email address.', 'keyless-auth'),
            'invalidkey'                => __('Your password reset link is invalid or has expired.', 'keyless-auth'),
            'expiredkey'                => __('Your password reset link has expired. Please request a new one.', 'keyless-auth'),

            // Custom plugin error codes
            'chrmrtns_kla_adminapp_error' => __('Your account is pending admin approval. Please contact the site administrator.', 'keyless-auth'),
            'chrmrtns_kla_error_token'    => __('Your login link is invalid or has expired. Please request a new one.', 'keyless-auth'),
        );

        // Check for specific error code
        if (!empty($error_code) && isset($error_messages[$error_code])) {
            return $error_messages[$error_code];
        }

        // Handle login status messages
        if ('failed' === $login_status) {
            return __('Login failed. Please check your credentials and try again.', 'keyless-auth');
        }

        return '';
    }

    /**
     * Format login success message
     *
     * @return string Formatted HTML
     */
    public static function formatLoginSentMessage() {
        return self::formatSuccessMessage(
            __('Check your email! We\'ve sent you a magic link to log in.', 'keyless-auth')
        );
    }

    /**
     * Format generic info message
     *
     * @param string $message Info message text.
     * @return string Formatted HTML
     */
    public static function formatInfoMessage($message) {
        return '<div class="chrmrtns-info-box chrmrtns-message-box">' .
               '<p>' . wp_kses_post($message) . '</p>' .
               '</div>';
    }

    /**
     * Check if login was sent successfully (from URL parameter)
     *
     * @return bool True if login email was sent
     */
    public static function wasLoginSent() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for display only
        return isset($_GET['chrmrtns_kla_sent']) && '1' === $_GET['chrmrtns_kla_sent'];
    }

    /**
     * Check for error parameters in URL
     *
     * @return string Error code if present, empty string otherwise
     */
    public static function getErrorFromUrl() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameters for display only
        if (isset($_GET['chrmrtns_kla_error_token'])) {
            return 'chrmrtns_kla_error_token';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameters for display only
        if (isset($_GET['chrmrtns_kla_adminapp_error'])) {
            return 'chrmrtns_kla_adminapp_error';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameters for display only
        if (isset($_GET['login_error'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for display only
            return sanitize_text_field(wp_unslash($_GET['login_error']));
        }

        return '';
    }

    /**
     * Get login status from URL
     *
     * @return string Login status or empty string
     */
    public static function getLoginStatus() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for display only
        if (isset($_GET['login'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for display only
            return sanitize_text_field(wp_unslash($_GET['login']));
        }

        return '';
    }
}
