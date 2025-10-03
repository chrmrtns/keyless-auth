<?php
/**
 * 2FA Core functionality for Keyless Auth (Clean Patch Version)
 * Handles universal WordPress login interception and 2FA verification
 *
 * @since 2.4.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_KLA_2FA_Core {

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
        $this->totp = new Chrmrtns_KLA_TOTP();

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

        // TEMPORARY PATCH: Skip authentication hooks to avoid login conflicts
        // Only add admin notices for grace period warnings

        // Handle 2FA verification page (keep for manual 2FA setup)
        add_action('init', array($this, 'handle_2fa_verification'));

        // Add grace period notices (keep this working)
        add_action('admin_notices', array($this, 'show_grace_period_notices'));

        // Re-enable authentication hooks now that magic login form conflicts are resolved
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
            $login_url = class_exists('Chrmrtns_KLA_Admin') ? Chrmrtns_KLA_Admin::get_login_url() : wp_login_url();
            wp_redirect($login_url);
            exit;
        }

        $user_id = intval($_SESSION['chrmrtns_kla_2fa_user_id']);
        $user = get_user_by('id', $user_id);

        if (!$user) {
            $login_url = class_exists('Chrmrtns_KLA_Admin') ? Chrmrtns_KLA_Admin::get_login_url() : wp_login_url();
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

        // Calculate grace period remaining using the same logic as login enforcement
        $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));

        // Check if user has a 2FA requirement start date, otherwise use current time
        $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);
        if (empty($grace_start)) {
            // First time this user's role requires 2FA - start grace period now
            $grace_start = current_time('timestamp');
            update_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', $grace_start);
        }

        $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);
        $days_remaining = ceil(($grace_end - time()) / DAY_IN_SECONDS);

        if ($days_remaining > 0) {
            $message = get_option('chrmrtns_kla_2fa_grace_message',
                __('Your account requires 2FA setup within {days} days for security.', 'keyless-auth'));
            $message = str_replace('{days}', $days_remaining, $message);

            // Choose notice class and urgency level based on days remaining
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

            // Output notice with CSS class for styling via variables
            echo '<div class="notice chrmrtns-kla-grace-notice ' . esc_attr($notice_class) . ' ' . esc_attr($urgency_class) . ' is-dismissible">';
            echo '<p><strong>' . esc_html($icon) . ' ' . esc_html__('Two-Factor Authentication Required', 'keyless-auth') . '</strong></p>';
            echo '<p>⏰ ' . esc_html($message) . '</p>';
            /* translators: %s: shortcode name in code tags */
            echo '<p><em>🚀 ' . sprintf(esc_html__('Use the shortcode %s to set up 2FA.', 'keyless-auth'), '<code>[keyless-auth-2fa]</code>') . '</em></p>';
            echo '</div>';

            // Add inline styles using CSS variables (only output once)
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
     * Placeholder methods for disabled functionality in this patch version
     */

    public function show_2fa_setup_page() {
        // Redirect to admin area with instructions to use shortcode
        if (current_user_can('read')) {
            wp_redirect(admin_url('?chrmrtns_kla_setup_notice=1'));
            exit;
        }
        wp_die(esc_html__('Please use the shortcode [keyless-auth-2fa] on a page to set up 2FA.', 'keyless-auth'));
    }

    public function process_2fa_verification($user_id) {
        if (!session_id()) {
            session_start();
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification performed above in show_2fa_verification_page
        $code = isset($_POST['chrmrtns_2fa_code']) ? sanitize_text_field(wp_unslash($_POST['chrmrtns_2fa_code'])) : '';

        if (empty($code)) {
            wp_redirect(add_query_arg(array('action' => 'keyless-2fa-verify', 'error' => 'empty_code'), home_url()));
            exit;
        }

        // Verify code
        $valid = false;
        $is_backup_code = strlen($code) === 8;

        if ($is_backup_code) {
            // Check backup code
            $valid = $this->database->use_backup_code($user_id, $code);
        } else {
            // Check TOTP code
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

            // Check if this was from a magic login - if so, complete the magic login process
            $magic_login_data = get_transient('chrmrtns_kla_pending_magic_login_' . $user_id);
            if ($magic_login_data) {
                // Clean up the pending magic login data
                delete_transient('chrmrtns_kla_pending_magic_login_' . $user_id);

                // Clean up legacy user meta tokens (if any)
                delete_user_meta($user_id, 'chrmrtns_kla_login_token');
                delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');

                // Also run database token cleanup to remove expired tokens
                global $chrmrtns_kla_database;
                if ($chrmrtns_kla_database) {
                    $chrmrtns_kla_database->cleanup_expired_tokens($user_id);
                }

                // Redirect to the stored redirect URL
                wp_redirect($magic_login_data['redirect_url']);
                exit;
            }

            wp_redirect(admin_url());
            exit;
        } else {
            // Check if user is locked out
            $lockout_seconds = $this->totp->is_user_locked_out($user_id);
            $error = $lockout_seconds > 0 ? 'locked_out' : 'invalid_code';
            wp_redirect(add_query_arg(array('action' => 'keyless-2fa-verify', 'error' => $error), home_url()));
            exit;
        }
    }

    public function render_2fa_verification_form($user, $lockout_seconds) {
        // Always output our own standalone page with custom 2FA styling
        $error_message = '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking display parameter, not processing form data
        if (isset($_GET['error'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking display parameter, not processing form data
            $error = sanitize_text_field(wp_unslash($_GET['error']));
            switch ($error) {
                case 'empty_code':
                    $error_message = __('Please enter a verification code.', 'keyless-auth');
                    break;
                case 'invalid_code':
                    $error_message = __('Invalid verification code. Please try again.', 'keyless-auth');
                    break;
                case 'locked_out':
                    /* translators: %d: number of minutes until account is unlocked */
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
                        /* translators: %s: user's display name */
                        printf(esc_html__('Hi %s, please enter your authenticator code to complete login.', 'keyless-auth'), esc_html($user->display_name)); ?></p>
                    </div>

                    <?php if ($lockout_seconds > 0) : ?>
                        <div class="chrmrtns-2fa-error">
                            <strong><?php esc_html_e('Account Locked', 'keyless-auth'); ?></strong>
                            <p><?php
                            /* translators: %d: number of minutes until account is unlocked */
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
                    /* CSS Custom Properties for 2FA forms - Uses :root variables, defines fallbacks if not loaded */
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
                        background: var(--kla-background);
                        color: var(--kla-text);
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
                    .button-primary:active,
                    .button-primary:focus {
                        background: var(--kla-primary-active);
                        border-color: var(--kla-primary-active);
                        outline: 2px solid var(--kla-primary);
                        outline-offset: 2px;
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
                        transition: var(--kla-transition);
                    }
                    .chrmrtns-2fa-back a:hover {
                        color: var(--kla-primary-hover);
                        text-decoration: underline;
                    }
                    .chrmrtns-2fa-back a:focus {
                        outline: 2px solid var(--kla-primary);
                        outline-offset: 2px;
                    }
                </style>
            </div>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
        exit;
    }

    public function intercept_login($user, $username, $password) {
        // Skip if user authentication failed or username is empty
        if (is_wp_error($user) || empty($username)) {
            return $user;
        }

        // Skip if this is a magic login session (already handled by core magic login)
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Checking URL parameters for magic login detection, not processing form data
        if (isset($_GET['chrmrtns_kla_token']) && isset($_GET['chrmrtns_kla_user_id'])) {
            return $user;
        }

        // Get user object
        if (is_object($user)) {
            $user_id = $user->ID;
        } else {
            $user_obj = get_user_by('login', $username) ?: get_user_by('email', $username);
            if (!$user_obj) {
                return $user;
            }
            $user_id = $user_obj->ID;
        }

        // Check if user needs 2FA
        if (!$this->user_needs_2fa($user_id)) {
            return $user;
        }

        // Start session and store user ID for 2FA verification
        if (!session_id()) {
            session_start();
        }
        $_SESSION['chrmrtns_kla_2fa_user_id'] = $user_id;

        // Redirect to 2FA verification page (own page, not wp-login.php)
        wp_redirect(home_url('/?action=keyless-2fa-verify'));
        exit;
    }

    public function process_2fa_after_login($user_login, $user) {
        // Additional post-login processing for 2FA users
        $user_id = $user->ID;

        if ($this->user_has_2fa($user_id)) {
            // Log successful 2FA login
            update_user_meta($user_id, 'chrmrtns_kla_2fa_last_login', current_time('timestamp'));
        }
    }

    public function check_role_enforcement($user_login, $user) {
        $user_id = $user->ID;

        // Check if user's role requires 2FA but they don't have it set up
        if ($this->user_role_requires_2fa($user_id) && !$this->user_has_2fa($user_id)) {
            // Check grace period
            $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
            $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);

            if (empty($grace_start)) {
                // First time this user's role requires 2FA - start grace period now
                $grace_start = current_time('timestamp');
                update_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', $grace_start);

                // Send 2FA notification email
                $this->send_2fa_notification_email($user_id);
            }

            $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);

            // If grace period expired, force logout
            if (time() > $grace_end) {
                wp_logout();
                wp_redirect(wp_login_url('?chrmrtns_kla_2fa_required=1'));
                exit;
            }
        }
    }

    public function enforce_2fa_setup() {
        if (!is_admin() || !current_user_can('read')) {
            return;
        }

        $user_id = get_current_user_id();

        // Check if user's role requires 2FA but they don't have it set up
        if ($this->user_role_requires_2fa($user_id) && !$this->user_has_2fa($user_id)) {
            // Check grace period
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
                wp_redirect(home_url('/?action=keyless-2fa-setup'));
                exit;
            }
        }
    }

    public function maybe_show_2fa_form() {
        // Additional login form modifications if needed
        // This method is called by login_form hook
        return;
    }

    /**
     * Send 2FA notification email to user
     *
     * @param int $user_id User ID
     * @return bool Success status
     */
    public function send_2fa_notification_email($user_id) {
        $user = get_user_by('id', $user_id);
        if (!$user) {
            return false;
        }

        // Check if email was already sent to prevent spam
        $email_sent = get_user_meta($user_id, 'chrmrtns_kla_2fa_notification_sent', true);
        if ($email_sent) {
            return false; // Already sent
        }

        // Calculate grace period
        $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
        $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);

        if (empty($grace_start)) {
            $grace_start = current_time('timestamp');
            update_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', $grace_start);
        }

        $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);
        $days_remaining = ceil(($grace_end - time()) / DAY_IN_SECONDS);

        // Setup URL - use custom 2FA setup page if configured, otherwise login page
        $custom_2fa_setup_url = get_option('chrmrtns_kla_custom_2fa_setup_url', '');
        if (!empty($custom_2fa_setup_url)) {
            $setup_url = esc_url($custom_2fa_setup_url);
        } else {
            $setup_url = wp_login_url();
        }

        // Get email template
        if (class_exists('Chrmrtns_KLA_Email_Templates')) {
            $email_templates = new Chrmrtns_KLA_Email_Templates();
            $email_content = $email_templates->get_2fa_notification_template(
                $user->user_email,
                $user->display_name,
                $days_remaining,
                $setup_url
            );
        } else {
            // Fallback simple email if template class not available
            $site_name = get_bloginfo('name');
            $email_content = sprintf(
                /* translators: 1: user display name, 2: site name, 3: days remaining, 4: setup URL */
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

        // Send email
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail($user->user_email, $subject, $email_content, $headers);

        if ($sent) {
            // Mark as sent to prevent duplicate emails
            update_user_meta($user_id, 'chrmrtns_kla_2fa_notification_sent', current_time('timestamp'));

            // Log the email if mail logger is available
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
     * Check if user needs 2FA notification email
     *
     * @param int $user_id User ID
     * @return bool
     */
    public function user_needs_2fa_notification($user_id) {
        // Only send if role requires 2FA but user doesn't have it set up yet
        if (!$this->user_role_requires_2fa($user_id) || $this->user_has_2fa($user_id)) {
            return false;
        }

        // Don't send if already sent
        $email_sent = get_user_meta($user_id, 'chrmrtns_kla_2fa_notification_sent', true);
        if ($email_sent) {
            return false;
        }

        return true;
    }

    /**
     * Schedule 2FA reminder emails cron job
     */
    public function schedule_2fa_reminders() {
        if (!wp_next_scheduled('chrmrtns_kla_2fa_reminder_emails')) {
            wp_schedule_event(time(), 'daily', 'chrmrtns_kla_2fa_reminder_emails');
        }
    }

    /**
     * Send 2FA reminder emails to users approaching deadline
     * Called by daily cron job
     */
    public function send_2fa_reminder_emails() {
        if (!$this->is_2fa_system_enabled()) {
            return;
        }

        $required_roles = get_option('chrmrtns_kla_2fa_required_roles', array());
        if (empty($required_roles)) {
            return;
        }

        // Get all users with required roles
        $users = get_users(array(
            'role__in' => $required_roles,
            'fields' => 'all',
        ));

        foreach ($users as $user) {
            $user_id = $user->ID;

            // Skip if user already has 2FA set up
            if ($this->user_has_2fa($user_id)) {
                continue;
            }

            // Check grace period
            $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
            $grace_start = get_user_meta($user_id, 'chrmrtns_kla_2fa_required_since', true);

            if (empty($grace_start)) {
                continue; // No grace period started yet
            }

            $grace_end = $grace_start + ($grace_days * DAY_IN_SECONDS);
            $days_remaining = ceil(($grace_end - time()) / DAY_IN_SECONDS);

            // Send reminder emails at 3 days and 1 day remaining
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

    public function get_2fa_statistics() {
        // Return empty stats for this patch version
        return array(
            'users_with_2fa' => 0,
            '2fa_logins_this_month' => 0,
            'failed_attempts_this_week' => 0
        );
    }

    /**
     * Handle 2FA system being enabled
     *
     * @param bool $old_value Previous value
     * @param bool $new_value New value
     */
    public function on_2fa_system_enabled($old_value, $new_value) {
        // Only trigger if 2FA was just enabled (false -> true)
        if (!$old_value && $new_value) {
            $this->send_2fa_notifications_to_all_required_users();
        }
    }

    /**
     * Handle 2FA required roles being changed
     *
     * @param array $old_value Previous roles array
     * @param array $new_value New roles array
     */
    public function on_2fa_roles_changed($old_value, $new_value) {
        $old_roles = is_array($old_value) ? $old_value : array();
        $new_roles = is_array($new_value) ? $new_value : array();

        // Find newly added roles
        $added_roles = array_diff($new_roles, $old_roles);

        if (!empty($added_roles)) {
            // Send notifications to users with newly required roles
            $this->send_2fa_notifications_to_role_users($added_roles);
        }
    }

    /**
     * Handle user role being changed
     *
     * @param int $user_id User ID
     * @param string $new_role New role
     * @param array $old_roles Previous roles
     */
    public function on_user_role_changed($user_id, $new_role, $old_roles) {
        // Check if new role requires 2FA
        $required_roles = get_option('chrmrtns_kla_2fa_required_roles', array());

        if (in_array($new_role, $required_roles)) {
            // Check if user didn't have 2FA requirement before
            $had_2fa_requirement = false;
            foreach ($old_roles as $old_role) {
                if (in_array($old_role, $required_roles)) {
                    $had_2fa_requirement = true;
                    break;
                }
            }

            // If user now needs 2FA but didn't before, send notification
            if (!$had_2fa_requirement) {
                $this->send_2fa_notification_email($user_id);
            }
        }
    }

    /**
     * Send 2FA notifications to all users who require it
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
            // Only send if user doesn't already have 2FA set up
            if (!$this->user_has_2fa($user->ID)) {
                $this->send_2fa_notification_email($user->ID);
            }
        }
    }

    /**
     * Send 2FA notifications to users with specific roles
     *
     * @param array $roles Array of role names
     */
    private function send_2fa_notifications_to_role_users($roles) {
        $users = get_users(array(
            'role__in' => $roles,
            'fields' => array('ID')
        ));

        foreach ($users as $user) {
            // Only send if user doesn't already have 2FA set up
            if (!$this->user_has_2fa($user->ID)) {
                $this->send_2fa_notification_email($user->ID);
            }
        }
    }
}