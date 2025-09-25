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

        // Authentication hooks disabled in this patch version to prevent login issues
        // These will be re-enabled in a future update with proper conflict resolution
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
        return $this->user_has_2fa($user_id) || $this->user_role_requires_2fa($user_id);
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

            // Choose notice class and colors based on days remaining
            $notice_class = 'notice-info';
            $bg_color = '#e8f4fd';
            $border_color = '#0073aa';
            $icon = '🔐';

            if ($days_remaining <= 3) {
                $notice_class = 'notice-error';
                $bg_color = '#fef7f7';
                $border_color = '#dc3232';
                $icon = '🚨';
            } elseif ($days_remaining <= 7) {
                $notice_class = 'notice-warning';
                $bg_color = '#fff8e5';
                $border_color = '#ffb900';
                $icon = '⚠️';
            }

            echo '<div class="notice ' . esc_attr($notice_class) . ' is-dismissible" style="background-color: ' . esc_attr($bg_color) . '; border-left-color: ' . esc_attr($border_color) . '; padding: 15px; margin: 15px 0; box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);">';
            echo '<p><strong>' . esc_html($icon) . ' ' . esc_html__('Two-Factor Authentication Required', 'keyless-auth') . '</strong></p>';
            echo '<p>⏰ ' . esc_html($message) . '</p>';
            /* translators: %s: shortcode name in code tags */
            echo '<p><em>🚀 ' . sprintf(esc_html__('Use the shortcode %s to set up 2FA.', 'keyless-auth'), '<code>[keyless-auth-2fa]</code>') . '</em></p>';
            echo '</div>';
        }
    }

    /**
     * Placeholder methods for disabled functionality in this patch version
     */

    public function show_2fa_setup_page() {
        // Simplified setup page - full implementation in next version
        wp_die(esc_html__('2FA setup page is temporarily disabled in this patch version. Please use the shortcode [keyless-auth-2fa] on a page to set up 2FA.', 'keyless-auth'));
    }

    public function process_2fa_verification($user_id) {
        // Simplified verification - full implementation in next version
        wp_die(esc_html__('2FA verification is temporarily disabled in this patch version. Authentication hooks will be restored in the next update.', 'keyless-auth'));
    }

    public function render_2fa_verification_form($user, $lockout_seconds) {
        // Simplified form - full implementation in next version
        wp_die(esc_html__('2FA verification form is temporarily disabled in this patch version.', 'keyless-auth'));
    }

    public function intercept_login($user, $username, $password) {
        // Authentication interception disabled in this patch version
        return $user;
    }

    public function process_2fa_after_login($user_login, $user) {
        // Post-login processing disabled in this patch version
    }

    public function check_role_enforcement($user_login, $user) {
        // Role enforcement disabled in this patch version
    }

    public function enforce_2fa_setup() {
        // Setup enforcement disabled in this patch version
    }

    public function maybe_show_2fa_form() {
        // Form display disabled in this patch version
    }

    public function get_2fa_statistics() {
        // Return empty stats for this patch version
        return array(
            'users_with_2fa' => 0,
            '2fa_logins_this_month' => 0,
            'failed_attempts_this_week' => 0
        );
    }
}