<?php
/**
 * 2FA Core functionality for Keyless Auth
 * Handles universal WordPress login interception and 2FA verification
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Security\TwoFA;

use Chrmrtns\KeylessAuth\Email\Templates as EmailTemplates;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Core {

    /**
     * TOTP instance
     */
    private $totp;

    /**
     * Database instance
     */
    private $database;

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - private for singleton
     */
    private function __construct() {
        $this->totp = new TOTP();

        global $chrmrtns_kla_database;
        $this->database = $chrmrtns_kla_database;

        // Initialize hooks based on system state
        $this->init_hooks();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {}

    /**
     * Check if 2FA is emergency disabled
     *
     * @return bool
     */
    private function is_emergency_disabled() {
        // Emergency disable via wp-config.php constant
        if (defined('CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY') && CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY === true) {
            return true;
        }

        // Emergency disable via database option (for easier recovery)
        if (get_option('chrmrtns_kla_2fa_emergency_disable', false)) {
            return true;
        }

        return false;
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Don't initialize ANY 2FA hooks if emergency disabled - completely disable 2FA system
        if ($this->is_emergency_disabled()) {
            return;
        }

        // Don't initialize hooks if 2FA system is disabled
        if (!get_option('chrmrtns_kla_2fa_enabled', false)) {
            return;
        }

        // Handle 2FA verification page (use template_redirect to ensure WooCommerce cart is ready)
        add_action('template_redirect', array($this, 'handle_2fa_verification'));

        // Add grace period notices
        add_action('admin_notices', array($this, 'show_grace_period_notices'));

        // Authentication hooks
        add_filter('authenticate', array($this, 'intercept_login'), 30, 3);
        add_action('wp_login', array($this, 'process_2fa_after_login'), 10, 2);
        add_action('wp_login', array($this, 'check_role_enforcement'), 15, 2);
        add_action('admin_init', array($this, 'enforce_2fa_setup'));
        add_action('login_form', array($this, 'maybe_show_2fa_form'));

        // Schedule 2FA reminder emails
        add_action('init', array($this, 'schedule_2fa_reminders'));
        add_action('chrmrtns_kla_2fa_reminder_emails', array($this, 'send_2fa_reminder_emails'));

        // Trigger emails when 2FA settings are changed
        add_action('update_option_chrmrtns_kla_2fa_enabled', array($this, 'on_2fa_system_enabled'), 10, 2);
        add_action('update_option_chrmrtns_kla_2fa_required_roles', array($this, 'on_2fa_roles_changed'), 10, 2);
        add_action('set_user_role', array($this, 'on_user_role_changed'), 10, 3);
    }

    /**
     * Check if 2FA system is enabled globally
     *
     * @return bool
     */
    public function is_2fa_system_enabled() {
        // Check emergency disable first
        if ($this->is_emergency_disabled()) {
            return false;
        }

        return get_option('chrmrtns_kla_2fa_enabled', false);
    }

    /**
     * Check if user has 2FA enabled
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function user_has_2fa($user_id) {
        $settings = $this->database->get_user_2fa_settings($user_id);
        return $settings && $settings->totp_enabled;
    }

    /**
     * Check if user role requires 2FA
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function user_role_requires_2fa($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        $required_roles = get_option('chrmrtns_kla_2fa_required_roles', array());
        if (empty($required_roles)) {
            return false;
        }

        return !empty(array_intersect($user->roles, $required_roles));
    }

    /**
     * Check if user needs 2FA (has it enabled OR role requires it)
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function user_needs_2fa($user_id) {
        // Only require 2FA verification if user actually has 2FA set up
        // Role requirements without 2FA setup should go through grace period logic instead
        return $this->user_has_2fa($user_id);
    }

    /**
     * Handle 2FA verification page requests
     */
    public function handle_2fa_verification() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for route detection, no form processing
        if (!isset($_GET['action'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for route detection, no form processing
        $action = sanitize_text_field(wp_unslash($_GET['action']));
        switch ($action) {
            case 'keyless-2fa-verify':
                $this->show_2fa_verification_page();
                break;
            case 'keyless-2fa-setup':
                $this->show_2fa_setup_page();
                break;
        }
    }

    /**
     * Show 2FA verification page
     */
    private function show_2fa_verification_page() {
        if (!session_id()) {
            session_start();
        }

        if (empty($_SESSION['chrmrtns_kla_2fa_user_id'])) {
            $login_url = class_exists('Chrmrtns\\KeylessAuth\\Admin\\Admin') ?
                \Chrmrtns\KeylessAuth\Admin\Admin::get_login_url() : wp_login_url();
            wp_redirect($login_url);
            exit;
        }

        $user_id = intval($_SESSION['chrmrtns_kla_2fa_user_id']);
        $user = get_user_by('id', $user_id);

        if (!$user) {
            $login_url = class_exists('Chrmrtns\\KeylessAuth\\Admin\\Admin') ?
                \Chrmrtns\KeylessAuth\Admin\Admin::get_login_url() : wp_login_url();
            wp_redirect($login_url);
            exit;
        }

        // Handle form submission
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification performed in condition
        if ($_POST && isset($_POST['chrmrtns_2fa_code'], $_POST['chrmrtns_2fa_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_2fa_nonce'])), 'chrmrtns_2fa_verify')) {
            $this->process_2fa_verification($user_id);
        }

        // Check for lockout
        $lockout_seconds = $this->totp->is_user_locked_out($user_id);

        $this->render_2fa_verification_form($user, $lockout_seconds);
    }

    /**
     * Show grace period notices in admin
     */
    public function show_grace_period_notices() {
        if (!current_user_can('read')) {
            return;
        }

        $user_id = get_current_user_id();

        // Show emergency disable notice if 2FA is disabled via wp-config
        if (defined('CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY') && CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY === true && current_user_can('manage_options')) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>' . esc_html__('EMERGENCY: 2FA System Disabled!', 'keyless-auth') . '</strong></p>';
            echo '<p>' . esc_html__('Two-Factor Authentication is completely disabled via wp-config.php constant CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY.', 'keyless-auth') . '</p>';
            echo '<p>' . esc_html__('This is for emergency access only. Remove this constant from wp-config.php immediately after resolving the issue.', 'keyless-auth') . '</p>';
            echo '<p><code>define(\'CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY\', true);</code></p>';
            echo '</div>';
        }

        // Show emergency admin notice if user bypassed 2FA
        if (get_transient('chrmrtns_kla_emergency_admin_notice_' . $user_id)) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>' . esc_html__('URGENT: Two-Factor Authentication Required!', 'keyless-auth') . '</strong></p>';
            echo '<p>' . esc_html__('You have bypassed 2FA protection as the only administrator without 2FA setup. This is a security risk!', 'keyless-auth') . '</p>';
            echo '<p>' . esc_html__('Please set up 2FA immediately to secure your account.', 'keyless-auth') . '</p>';
            /* translators: %s: shortcode name in code tags */
            echo '<p><em>' . sprintf(esc_html__('Use the shortcode %s to set up 2FA.', 'keyless-auth'), '<code>[keyless-auth-2fa]</code>') . '</em></p>';
            echo '</div>';
        }

        // Only show for users whose role requires 2FA but haven't set it up
        if (!$this->user_role_requires_2fa($user_id) || $this->user_has_2fa($user_id)) {
            return;
        }

        // Calculate grace period remaining
        $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
        $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);

        if (empty($grace_start)) {
            $grace_start = current_time('timestamp');
            update_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', $grace_start);
        }

        $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);
        $days_remaining = ceil(($grace_end - time()) / DAY_IN_SECONDS);

        if ($days_remaining > 0) {
            $message = get_option('chrmrtns_kla_2fa_grace_message',
                __('Your account requires 2FA setup within {days} days for security.', 'keyless-auth'));
            $message = str_replace('{days}', $days_remaining, $message);

            // Choose notice class based on urgency
            $notice_class = 'notice-info';
            $urgency_class = 'kla-notice-normal';
            $icon = '🔐';

            if ($days_remaining <= 3) {
                $notice_class = 'notice-error';
                $urgency_class = 'kla-notice-urgent';
                $icon = '🚨';
            } elseif ($days_remaining <= 7) {
                $notice_class = 'notice-warning';
                $urgency_class = 'kla-notice-warning';
                $icon = '⚠️';
            }

            echo '<div class="notice chrmrtns-kla-grace-notice ' . esc_attr($notice_class) . ' ' . esc_attr($urgency_class) . ' is-dismissible">';
            echo '<p><strong>' . esc_html($icon) . ' ' . esc_html__('Two-Factor Authentication Required', 'keyless-auth') . '</strong></p>';
            echo '<p>⏰ ' . esc_html($message) . '</p>';
            /* translators: %s: shortcode name in code tags */
            echo '<p><em>🚀 ' . sprintf(esc_html__('Use the shortcode %s to set up 2FA.', 'keyless-auth'), '<code>[keyless-auth-2fa]</code>') . '</em></p>';
            echo '</div>';

            // Add inline styles (only output once)
            static $grace_notice_styles_added = false;
            if (!$grace_notice_styles_added) {
                echo '<style>
                    :root {
                        --kla-primary: #0073aa;
                        --kla-error: #d63638;
                        --kla-warning: #dba617;
                        --kla-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
                    }
                    .chrmrtns-kla-grace-notice {
                        padding: 15px !important;
                        margin: 15px 0 !important;
                        box-shadow: var(--kla-shadow) !important;
                        border-left: 4px solid var(--kla-primary) !important;
                    }
                    .chrmrtns-kla-grace-notice.kla-notice-normal {
                        background-color: #f0f6fc !important;
                        border-left-color: var(--kla-primary) !important;
                    }
                    .chrmrtns-kla-grace-notice.kla-notice-warning {
                        background-color: #fff8e5 !important;
                        border-left-color: var(--kla-warning) !important;
                    }
                    .chrmrtns-kla-grace-notice.kla-notice-urgent {
                        background-color: #fef7f7 !important;
                        border-left-color: var(--kla-error) !important;
                    }
                </style>';
                $grace_notice_styles_added = true;
            }
        }
    }

    /**
     * Show 2FA setup page
     */
    public function show_2fa_setup_page() {
        if (current_user_can('read')) {
            wp_redirect(admin_url('?chrmrtns_kla_setup_notice=1'));
            exit;
        }
        wp_die(esc_html__('Please use the shortcode [keyless-auth-2fa] on a page to set up 2FA.', 'keyless-auth'));
    }

    /**
     * Process 2FA verification
     */
    public function process_2fa_verification($user_id) {
        if (!session_id()) {
            session_start();
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification performed above
        $code = isset($_POST['chrmrtns_2fa_code']) ? sanitize_text_field(wp_unslash($_POST['chrmrtns_2fa_code'])) : '';

        if (empty($code)) {
            wp_redirect(add_query_arg(array('action' => 'keyless-2fa-verify', 'error' => 'empty_code'), home_url()));
            exit;
        }

        // Verify code
        $valid = false;
        $is_backup_code = strlen($code) === 8;

        if ($is_backup_code) {
            $valid = $this->database->use_backup_code($user_id, $code);
        } else {
            $settings = $this->database->get_user_2fa_settings($user_id);
            if ($settings && $settings->totp_secret && $this->totp->verify_code($code, $settings->totp_secret)) {
                $valid = true;
            }
        }

        if ($valid) {
            // Clear session data
            unset($_SESSION['chrmrtns_kla_2fa_user_id']);

            // Log the user in
            $user = get_user_by('id', $user_id);
            wp_set_current_user($user_id, $user->user_login);
            wp_set_auth_cookie($user_id, true);
            do_action('wp_login', $user->user_login, $user);

            // Check if this was from a magic login
            $magic_login_data = get_transient('chrmrtns_kla_pending_magic_login_' . $user_id);
            if ($magic_login_data) {
                delete_transient('chrmrtns_kla_pending_magic_login_' . $user_id);
                delete_user_meta($user_id, 'chrmrtns_kla_login_token');
                delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');

                global $chrmrtns_kla_database;
                if ($chrmrtns_kla_database) {
                    $chrmrtns_kla_database->cleanup_expired_tokens($user_id);
                }

                wp_redirect($magic_login_data['redirect_url']);
                exit;
            }

            wp_redirect(admin_url());
            exit;
        } else {
            $lockout_seconds = $this->totp->is_user_locked_out($user_id);
            $error = $lockout_seconds > 0 ? 'locked_out' : 'invalid_code';
            wp_redirect(add_query_arg(array('action' => 'keyless-2fa-verify', 'error' => $error), home_url()));
            exit;
        }
    }

    /**
     * Render 2FA verification form
     */
    public function render_2fa_verification_form($user, $lockout_seconds) {
        $error_message = '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking display parameter
        if (isset($_GET['error'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $error = sanitize_text_field(wp_unslash($_GET['error']));
            switch ($error) {
                case 'empty_code':
                    $error_message = __('Please enter a verification code.', 'keyless-auth');
                    break;
                case 'invalid_code':
                    $error_message = __('Invalid verification code. Please try again.', 'keyless-auth');
                    break;
                case 'locked_out':
                    /* translators: %d: minutes until unlock */
                    $error_message = sprintf(__('Too many failed attempts. Please try again in %d minutes.', 'keyless-auth'), (int) ceil($lockout_seconds / 60));
                    break;
            }
        }

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php esc_html_e('Two-Factor Authentication', 'keyless-auth'); ?> &lsaquo; <?php bloginfo('name'); ?></title>
            <?php wp_head(); ?>
        </head>
        <body>
            <div class="chrmrtns-2fa-wrapper">
                <div class="chrmrtns-2fa-container">
                    <div class="chrmrtns-2fa-header">
                        <h2><?php esc_html_e('Two-Factor Authentication Required', 'keyless-auth'); ?></h2>
                        <p><?php
                        /* translators: %s: user display name */
                        printf(esc_html__('Hi %s, please enter your authenticator code to complete login.', 'keyless-auth'), esc_html($user->display_name)); ?></p>
                    </div>

                    <?php if ($lockout_seconds > 0) : ?>
                        <div class="chrmrtns-2fa-error">
                            <strong><?php esc_html_e('Account Locked', 'keyless-auth'); ?></strong>
                            <p><?php
                            /* translators: %d: minutes until unlock */
                            printf(esc_html__('Too many failed attempts. Please try again in %d minutes.', 'keyless-auth'), (int) ceil($lockout_seconds / 60)); ?></p>
                        </div>
                    <?php else : ?>
                        <?php if ($error_message) : ?>
                            <div class="chrmrtns-2fa-error">
                                <strong><?php esc_html_e('Error:', 'keyless-auth'); ?></strong>
                                <p><?php echo esc_html($error_message); ?></p>
                            </div>
                        <?php endif; ?>

                        <form method="post" id="chrmrtns-2fa-form">
                            <?php wp_nonce_field('chrmrtns_2fa_verify', 'chrmrtns_2fa_nonce'); ?>
                            <p>
                                <label for="chrmrtns_2fa_code"><?php esc_html_e('Verification Code', 'keyless-auth'); ?></label>
                                <input type="text" name="chrmrtns_2fa_code" id="chrmrtns_2fa_code" class="input" maxlength="8" autocomplete="off" required placeholder="<?php esc_attr_e('Enter 6-digit code or 8-digit backup code', 'keyless-auth'); ?>">
                            </p>

                            <p class="submit">
                                <input type="submit" class="button-primary" value="<?php esc_attr_e('Verify', 'keyless-auth'); ?>">
                            </p>
                        </form>

                        <p class="chrmrtns-2fa-help">
                            <small><?php esc_html_e('Open your authenticator app and enter the 6-digit code, or use one of your 8-digit backup codes.', 'keyless-auth'); ?></small>
                        </p>
                    <?php endif; ?>

                    <p class="chrmrtns-2fa-back">
                        <a href="<?php echo esc_url(wp_login_url()); ?>"><?php esc_html_e('← Back to login', 'keyless-auth'); ?></a>
                    </p>
                </div>

                <style>
                    :root {
                        --kla-primary: #0073aa;
                        --kla-primary-hover: #005a87;
                        --kla-primary-active: #004a70;
                        --kla-error: #d63638;
                        --kla-error-light: #f8d7da;
                        --kla-text: #2c3338;
                        --kla-text-light: #646970;
                        --kla-border: #8c8f94;
                        --kla-border-light: #dcdcde;
                        --kla-background: #ffffff;
                        --kla-background-alt: #f6f7f7;
                        --kla-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
                        --kla-shadow-hover: 0 2px 8px rgba(0, 0, 0, 0.1);
                        --kla-radius: 4px;
                        --kla-transition: all 0.2s ease;
                    }

                    body {
                        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                        background: var(--kla-background-alt);
                        margin: 0;
                        padding: 0;
                    }
                    .chrmrtns-2fa-wrapper {
                        max-width: 400px;
                        margin: 50px auto;
                        padding: 20px;
                    }
                    .chrmrtns-2fa-container {
                        background: var(--kla-background);
                        padding: 30px;
                        border-radius: var(--kla-radius);
                        box-shadow: var(--kla-shadow-hover);
                    }
                    .chrmrtns-2fa-header h2 {
                        margin: 0 0 10px 0;
                        color: var(--kla-text);
                        font-size: 24px;
                        text-align: center;
                    }
                    .chrmrtns-2fa-header p {
                        margin: 0 0 25px 0;
                        color: var(--kla-text-light);
                        text-align: center;
                        font-size: 16px;
                    }
                    .chrmrtns-2fa-error {
                        background: var(--kla-error-light);
                        border: 1px solid var(--kla-error);
                        border-radius: var(--kla-radius);
                        padding: 15px;
                        margin-bottom: 20px;
                    }
                    .chrmrtns-2fa-error strong {
                        display: block;
                        margin-bottom: 5px;
                        color: var(--kla-error);
                    }
                    .chrmrtns-2fa-error p {
                        margin: 0;
                        color: var(--kla-error);
                    }
                    #chrmrtns-2fa-form label {
                        display: block;
                        margin-bottom: 8px;
                        font-weight: 600;
                        color: var(--kla-text);
                    }
                    #chrmrtns_2fa_code {
                        width: 100%;
                        font-size: 18px;
                        text-align: center;
                        letter-spacing: 2px;
                        padding: 12px;
                        border: 2px solid var(--kla-border-light);
                        border-radius: var(--kla-radius);
                        box-sizing: border-box;
                        margin-bottom: 20px;
                    }
                    #chrmrtns_2fa_code:focus {
                        outline: none;
                        border-color: var(--kla-primary);
                        box-shadow: 0 0 0 1px var(--kla-primary);
                    }
                    .button-primary {
                        background: var(--kla-primary);
                        border: 1px solid var(--kla-primary);
                        color: #ffffff;
                        border-radius: var(--kla-radius);
                        cursor: pointer;
                        font-size: 16px;
                        padding: 12px 24px;
                        width: 100%;
                        transition: var(--kla-transition);
                    }
                    .button-primary:hover {
                        background: var(--kla-primary-hover);
                        border-color: var(--kla-primary-hover);
                    }
                    .chrmrtns-2fa-help {
                        text-align: center;
                        margin: 20px 0;
                    }
                    .chrmrtns-2fa-help small {
                        color: var(--kla-text-light);
                        font-style: italic;
                        font-size: 14px;
                    }
                    .chrmrtns-2fa-back {
                        text-align: center;
                        margin-top: 25px;
                    }
                    .chrmrtns-2fa-back a {
                        color: var(--kla-primary);
                        text-decoration: none;
                        font-size: 14px;
                    }
                    .chrmrtns-2fa-back a:hover {
                        color: var(--kla-primary-hover);
                        text-decoration: underline;
                    }
                </style>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Intercept login for 2FA check
     */
    public function intercept_login($user, $username, $password) {
        if (is_wp_error($user) || empty($username)) {
            return $user;
        }

        // Skip if this is a magic login
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (isset($_GET['chrmrtns_kla_token']) && isset($_GET['chrmrtns_kla_user_id'])) {
            return $user;
        }

        $user_id = is_object($user) ? $user->ID : (get_user_by('login', $username) ?: get_user_by('email', $username))->ID;

        if (!$this->user_needs_2fa($user_id)) {
            return $user;
        }

        // Start session for 2FA verification
        if (!session_id()) {
            session_start();
        }
        $_SESSION['chrmrtns_kla_2fa_user_id'] = $user_id;

        wp_redirect(home_url('/?action=keyless-2fa-verify'));
        exit;
    }

    /**
     * Process after login
     */
    public function process_2fa_after_login($user_login, $user) {
        $user_id = $user->ID;

        if ($this->user_has_2fa($user_id)) {
            update_user_meta($user_id, 'chrmrtns_kla_2fa_last_login', current_time('timestamp'));
        }
    }

    /**
     * Check role enforcement
     */
    public function check_role_enforcement($user_login, $user) {
        $user_id = $user->ID;

        if ($this->user_role_requires_2fa($user_id) && !$this->user_has_2fa($user_id)) {
            $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
            $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);

            if (empty($grace_start)) {
                $grace_start = current_time('timestamp');
                update_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', $grace_start);
                $this->send_2fa_notification_email($user_id);
            }

            $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);

            if (time() > $grace_end) {
                wp_logout();
                wp_redirect(add_query_arg('chrmrtns_kla_2fa_required', '1', wp_login_url()));
                exit;
            }
        }
    }

    /**
     * Enforce 2FA setup
     */
    public function enforce_2fa_setup() {
        if (!is_admin() || !current_user_can('read')) {
            return;
        }

        $user_id = get_current_user_id();

        if ($this->user_role_requires_2fa($user_id) && !$this->user_has_2fa($user_id)) {
            $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
            $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);

            if (empty($grace_start)) {
                $grace_start = current_time('timestamp');
                update_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', $grace_start);
            }

            $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);

            if (time() > $grace_end) {
                wp_redirect(home_url('/?action=keyless-2fa-setup'));
                exit;
            }
        }
    }

    /**
     * Maybe show 2FA form
     */
    public function maybe_show_2fa_form() {
        return;
    }

    /**
     * Send 2FA notification email
     */
    public function send_2fa_notification_email($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        $email_sent = get_user_meta($user_id, 'chrmrtns_kla_2fa_notification_sent', true);
        if ($email_sent) {
            return false;
        }

        $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
        $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);

        if (empty($grace_start)) {
            $grace_start = current_time('timestamp');
            update_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', $grace_start);
        }

        $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);
        $days_remaining = ceil(($grace_end - time()) / DAY_IN_SECONDS);

        $custom_2fa_setup_url = get_option('chrmrtns_kla_custom_2fa_setup_url', '');
        $setup_url = !empty($custom_2fa_setup_url) ? esc_url($custom_2fa_setup_url) : wp_login_url();

        // Get email template
        if (class_exists('Chrmrtns\\KeylessAuth\\Email\\Templates')) {
            $email_templates = new EmailTemplates();
            $email_content = $email_templates->get_2fa_notification_template(
                $user->user_email,
                $user->display_name,
                $days_remaining,
                $setup_url
            );
        } else {
            $site_name = get_bloginfo('name');
            $email_content = sprintf(
                /* translators: 1: display name, 2: site name, 3: days remaining, 4: setup URL */
                __('Hello %1$s,

Your account on %2$s now requires Two-Factor Authentication (2FA) for enhanced security.

You have %3$d days to set up 2FA for your account.

Please visit: %4$s

Thank you for helping keep our site secure.

Best regards,
%2$s Team', 'keyless-auth'),
                $user->display_name,
                $site_name,
                $days_remaining,
                $setup_url
            );
        }

        $subject = sprintf(
            /* translators: %s: site name */
            __('[%s] Account Security Setup', 'keyless-auth'),
            get_bloginfo('name')
        );

        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($user->user_email, $subject, $email_content, $headers);

        if ($sent) {
            update_user_meta($user_id, 'chrmrtns_kla_2fa_notification_sent', current_time('timestamp'));

            global $chrmrtns_kla_database;
            if ($chrmrtns_kla_database && method_exists($chrmrtns_kla_database, 'log_email')) {
                $chrmrtns_kla_database->log_email(
                    $user_id,
                    $user->user_email,
                    $subject,
                    $email_content,
                    'sent',
                    null,
                    '2fa_notification'
                );
            }

            return true;
        }

        return false;
    }

    /**
     * Check if user needs 2FA notification
     */
    public function user_needs_2fa_notification($user_id) {
        if (!$this->user_role_requires_2fa($user_id) || $this->user_has_2fa($user_id)) {
            return false;
        }

        $email_sent = get_user_meta($user_id, 'chrmrtns_kla_2fa_notification_sent', true);
        return !$email_sent;
    }

    /**
     * Schedule 2FA reminders
     */
    public function schedule_2fa_reminders() {
        if (!wp_next_scheduled('chrmrtns_kla_2fa_reminder_emails')) {
            wp_schedule_event(time(), 'daily', 'chrmrtns_kla_2fa_reminder_emails');
        }
    }

    /**
     * Send 2FA reminder emails
     */
    public function send_2fa_reminder_emails() {
        if (!$this->is_2fa_system_enabled()) {
            return;
        }

        $required_roles = get_option('chrmrtns_kla_2fa_required_roles', array());
        if (empty($required_roles)) {
            return;
        }

        $users = get_users(array(
            'role__in' => $required_roles,
            'fields' => 'all',
        ));

        foreach ($users as $user) {
            $user_id = $user->ID;

            if ($this->user_has_2fa($user_id)) {
                continue;
            }

            $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
            $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);

            if (empty($grace_start)) {
                continue;
            }

            $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);
            $days_remaining = ceil(($grace_end - time()) / DAY_IN_SECONDS);

            if ($days_remaining == 3 || $days_remaining == 1) {
                $reminder_sent_key = 'chrmrtns_kla_2fa_reminder_' . $days_remaining . '_sent';
                $reminder_sent = get_user_meta($user_id, $reminder_sent_key, true);

                if (!$reminder_sent) {
                    $this->send_2fa_notification_email($user_id);
                    update_user_meta($user_id, $reminder_sent_key, current_time('timestamp'));
                }
            }
        }
    }

    /**
     * Get 2FA statistics
     */
    public function get_2fa_statistics() {
        return array(
            'users_with_2fa' => 0,
            '2fa_logins_this_month' => 0,
            'failed_attempts_this_week' => 0
        );
    }

    /**
     * Handle 2FA system being enabled
     */
    public function on_2fa_system_enabled($old_value, $new_value) {
        if (!$old_value && $new_value) {
            $this->send_2fa_notifications_to_all_required_users();
        }
    }

    /**
     * Handle 2FA roles changed
     */
    public function on_2fa_roles_changed($old_value, $new_value) {
        $old_roles = is_array($old_value) ? $old_value : array();
        $new_roles = is_array($new_value) ? $new_value : array();

        $added_roles = array_diff($new_roles, $old_roles);

        if (!empty($added_roles)) {
            $this->send_2fa_notifications_to_role_users($added_roles);
        }
    }

    /**
     * Handle user role changed
     */
    public function on_user_role_changed($user_id, $new_role, $old_roles) {
        $required_roles = get_option('chrmrtns_kla_2fa_required_roles', array());

        if (in_array($new_role, $required_roles)) {
            $had_2fa_requirement = false;
            foreach ($old_roles as $old_role) {
                if (in_array($old_role, $required_roles)) {
                    $had_2fa_requirement = true;
                    break;
                }
            }

            if (!$had_2fa_requirement) {
                $this->send_2fa_notification_email($user_id);
            }
        }
    }

    /**
     * Send notifications to all required users
     */
    private function send_2fa_notifications_to_all_required_users() {
        $required_roles = get_option('chrmrtns_kla_2fa_required_roles', array());

        if (empty($required_roles)) {
            return;
        }

        $users = get_users(array(
            'role__in' => $required_roles,
            'fields' => array('ID')
        ));

        foreach ($users as $user) {
            if (!$this->user_has_2fa($user->ID)) {
                $this->send_2fa_notification_email($user->ID);
            }
        }
    }

    /**
     * Send notifications to role users
     */
    private function send_2fa_notifications_to_role_users($roles) {
        $users = get_users(array(
            'role__in' => $roles,
            'fields' => array('ID')
        ));

        foreach ($users as $user) {
            if (!$this->user_has_2fa($user->ID)) {
                $this->send_2fa_notification_email($user->ID);
            }
        }
    }
}
