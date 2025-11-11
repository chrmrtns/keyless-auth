<?php
/**
 * URL Helper Class
 *
 * Handles URL generation, manipulation, and cleanup for Keyless Auth plugin.
 *
 * @package Keyless_Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Core;

/**
 * UrlHelper class
 *
 * Provides utility methods for URL handling throughout the plugin.
 */
class UrlHelper {

    /**
     * Get current page URL with plugin parameters removed
     *
     * @return string Current page URL
     */
    public static function getCurrentPageUrl() {
        global $wp;

        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));

            // Remove existing chrmrtns parameters to avoid accumulation
            $clean_uri = remove_query_arg(
                array(
                    'chrmrtns_kla_token',
                    'chrmrtns_kla_user_id',
                    'chrmrtns_kla_error_token',
                    'chrmrtns_kla_adminapp_error',
                    'chrmrtns_kla_sent',
                ),
                $request_uri
            );

            return home_url($clean_uri);
        }

        return home_url(add_query_arg(array(), $wp->request));
    }

    /**
     * Build magic link URL with token and parameters
     *
     * @param string $token      Login token.
     * @param int    $user_id    User ID.
     * @param string $redirect   Optional redirect URL after login.
     * @param string $base_url   Optional base URL (defaults to current page).
     * @return string Magic link URL
     */
    public static function buildMagicLinkUrl($token, $user_id, $redirect = '', $base_url = '') {
        if (empty($base_url)) {
            $base_url = self::getCurrentPageUrl();
        }

        $url_args = array(
            'chrmrtns_kla_token'   => $token,
            'chrmrtns_kla_user_id' => $user_id,
        );

        if (!empty($redirect)) {
            $url_args['chrmrtns_kla_redirect'] = rawurlencode($redirect);
        }

        return add_query_arg($url_args, $base_url);
    }

    /**
     * Add error parameters to URL
     *
     * @param string $url    Base URL.
     * @param array  $params Associative array of parameters to add.
     * @return string URL with error parameters
     */
    public static function addQueryParameters($url, $params) {
        return add_query_arg($params, $url);
    }

    /**
     * Remove plugin-specific query parameters from URL
     *
     * @param string $url URL to clean.
     * @return string Cleaned URL
     */
    public static function removePluginParameters($url) {
        return remove_query_arg(
            array(
                'chrmrtns_kla_token',
                'chrmrtns_kla_user_id',
                'chrmrtns_kla_error_token',
                'chrmrtns_kla_adminapp_error',
                'chrmrtns_kla_sent',
                'chrmrtns_kla_redirect',
            ),
            $url
        );
    }

    /**
     * Preserve specific parameters from current request
     *
     * Useful for wp-login.php redirect scenarios where we need to maintain
     * error messages, registration status, etc.
     *
     * @param array $params_to_check Parameters to look for in $_GET.
     * @return array Found parameters with their values
     */
    public static function preserveParameters($params_to_check) {
        $preserved = array();

        foreach ($params_to_check as $param) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameters for preservation only
            if (isset($_GET[$param])) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters from WordPress redirect
                $preserved[$param] = sanitize_text_field(wp_unslash($_GET[$param]));
            }
        }

        return $preserved;
    }

    /**
     * Get login URL (custom or default wp-login.php)
     *
     * @return string Login URL
     */
    public static function getLoginUrl() {
        $custom_login = get_option('chrmrtns_kla_custom_login_url', '');

        if (!empty($custom_login)) {
            return esc_url($custom_login);
        }

        return wp_login_url();
    }

    /**
     * Validate and sanitize redirect URL
     *
     * @param string $url      URL to validate.
     * @param string $fallback Fallback URL if validation fails.
     * @return string Validated URL
     */
    public static function validateRedirectUrl($url, $fallback = '') {
        if (empty($fallback)) {
            $fallback = admin_url();
        }

        return wp_validate_redirect($url, $fallback);
    }
}
