<?php
/**
 * Token Validator Class
 *
 * Handles magic link token validation and login processing for Keyless Auth plugin.
 *
 * @package Keyless_Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Security;

use Chrmrtns\KeylessAuth\Security\TwoFA\Core as TwoFACore;
use Chrmrtns\KeylessAuth\Admin\Pages\OptionsPage;
use Chrmrtns\KeylessAuth\Core\UrlHelper;

/**
 * TokenValidator class
 *
 * Manages token validation and login operations including:
 * - Magic link token validation
 * - Two-factor authentication integration
 * - Grace period handling for 2FA requirements
 * - User authentication and session management
 * - Post-login redirect handling
 */
class TokenValidator {

    /**
     * Security Manager instance
     *
     * @var SecurityManager
     */
    private $security_manager;

    /**
     * Constructor
     *
     * @param SecurityManager $security_manager Optional SecurityManager instance for dependency injection.
     */
    public function __construct($security_manager = null) {
        $this->security_manager = $security_manager;
    }

    /**
     * Handle login link clicks and validate tokens
     *
     * Processes magic link authentication by:
     * 1. Validating the token
     * 2. Checking 2FA requirements
     * 3. Logging the user in or redirecting to 2FA
     *
     * @return void
     */
    public function handle_login_link() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for login token, not form data
        if (!isset($_GET['chrmrtns_kla_token']) || !isset($_GET['chrmrtns_kla_user_id'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for login token, not form data
        $token = sanitize_text_field(wp_unslash($_GET['chrmrtns_kla_token']));
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for login token, not form data
        $user_id = intval($_GET['chrmrtns_kla_user_id']);

        // Validate token
        if (!$this->security_manager->validate_login_token($user_id, $token)) {
            wp_safe_redirect(add_query_arg('chrmrtns_kla_error_token', '1', UrlHelper::getCurrentPageUrl()));
            exit;
        }

        // Check if 2FA is required for this user
        $user = get_user_by('ID', $user_id);
        if ($user && class_exists('Chrmrtns\\KeylessAuth\\Security\\TwoFA\\Core')) {
            $this->handle_2fa_requirements($user_id, $token);
        }

        // If no 2FA required, proceed with normal login
        $this->complete_login($user_id);
    }

    /**
     * Handle two-factor authentication requirements
     *
     * Checks if user needs 2FA and redirects accordingly or handles grace period.
     *
     * @param int    $user_id User ID.
     * @param string $token   Login token.
     * @return void Exits if 2FA redirect is needed.
     */
    private function handle_2fa_requirements($user_id, $token) {
        $tfa_core = TwoFACore::get_instance();

        // Check if 2FA is enabled and required for this user
        if (!get_option('chrmrtns_kla_2fa_enabled', false)) {
            return;
        }

        global $chrmrtns_kla_database;
        $user_settings = $chrmrtns_kla_database ? $chrmrtns_kla_database->get_user_2fa_settings($user_id) : null;
        $role_required = $tfa_core->user_role_requires_2fa($user_id);

        // If user has 2FA enabled, redirect to 2FA verification
        if ($user_settings && $user_settings->totp_enabled) {
            $this->redirect_to_2fa_verification($user_id, $token);
        } elseif ($role_required) {
            // User's role requires 2FA but they don't have it set up yet
            $this->handle_2fa_grace_period($user_id);
        }
    }

    /**
     * Redirect user to 2FA verification page
     *
     * @param int    $user_id User ID.
     * @param string $token   Login token.
     * @return void Exits after redirect.
     */
    private function redirect_to_2fa_verification($user_id, $token) {
        // Get redirect URL
        $redirect_url = $this->get_post_login_redirect_url($user_id);
        $redirect_url = apply_filters('chrmrtns_kla_after_login_redirect', $redirect_url, $user_id);

        // Store the magic link token info for after 2FA verification
        set_transient('chrmrtns_kla_pending_magic_login_' . $user_id, array(
            'token' => $token,
            'redirect_url' => $redirect_url,
            'timestamp' => time()
        ), 300); // 5 minutes

        // Don't clean up the login token yet - we'll do it after 2FA verification

        // Set up session for 2FA verification
        if (!session_id()) {
            session_start();
        }
        $_SESSION['chrmrtns_kla_2fa_user_id'] = $user_id;
        $_SESSION['chrmrtns_kla_2fa_redirect'] = $redirect_url;

        // Redirect to 2FA verification page
        $tfa_verify_url = add_query_arg(array(
            'action' => 'keyless-2fa-verify',
            'magic_login' => '1'
        ), home_url());

        wp_safe_redirect($tfa_verify_url);
        exit;
    }

    /**
     * Handle 2FA grace period for users whose role requires 2FA
     *
     * If grace period has expired, redirects to 2FA setup.
     * Otherwise, allows login to proceed normally.
     *
     * @param int $user_id User ID.
     * @return void Exits if grace period expired.
     */
    private function handle_2fa_grace_period($user_id) {
        $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
        $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);

        if (empty($grace_start)) {
            // First time this user's role requires 2FA - start grace period now
            $grace_start = current_time('timestamp');
            update_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', $grace_start);
        }

        $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);

        // If grace period expired, redirect to 2FA setup
        if (time() > $grace_end) {
            wp_safe_redirect(add_query_arg(array('action' => 'keyless-2fa-setup', 'magic_login' => '1'), home_url()));
            exit;
        }
        // Grace period still active - allow login to proceed normally
    }

    /**
     * Complete the login process
     *
     * Authenticates the user, updates counters, cleans up tokens, and redirects.
     *
     * @param int $user_id User ID.
     * @return void Exits after redirect.
     */
    private function complete_login($user_id) {
        // Authenticate user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // Increment successful logins counter
        $current_count = get_option('chrmrtns_kla_successful_logins', 0);
        update_option('chrmrtns_kla_successful_logins', $current_count + 1);

        // Clean up token
        delete_user_meta($user_id, 'chrmrtns_kla_login_token');
        delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');

        // Get redirect URL and perform redirect
        $redirect_url = $this->get_post_login_redirect_url($user_id);
        $redirect_url = apply_filters('chrmrtns_kla_after_login_redirect', $redirect_url, $user_id);

        wp_safe_redirect(wp_validate_redirect($redirect_url, admin_url()));
        exit;
    }

    /**
     * Get post-login redirect URL
     *
     * Checks for custom redirect from shortcode or uses configured default.
     *
     * @param int $user_id User ID.
     * @return string Redirect URL.
     */
    private function get_post_login_redirect_url($user_id) {
        // Check for custom redirect from shortcode first
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for redirect, validated with esc_url_raw()
        $custom_redirect = isset($_GET['chrmrtns_kla_redirect']) ? esc_url_raw(wp_unslash($_GET['chrmrtns_kla_redirect'])) : '';

        if (!empty($custom_redirect)) {
            return $custom_redirect;
        }

        // Get redirect URL (custom or default)
        return class_exists('Chrmrtns\\KeylessAuth\\Admin\\Pages\\OptionsPage') ? OptionsPage::get_redirect_url($user_id) : admin_url();
    }
}
