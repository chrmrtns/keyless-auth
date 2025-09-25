<?php
/**
 * 2FA Core functionality for Keyless Auth
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
     * Constructor
     */
    public function __construct() {
        $this->totp = new Chrmrtns_KLA_TOTP();

        global $chrmrtns_kla_database;
        $this->database = $chrmrtns_kla_database;

        // Only initialize if 2FA system is enabled
        if ($this->is_2fa_system_enabled()) {
            $this->init_hooks();
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Intercept all WordPress logins
        add_filter('authenticate', array($this, 'intercept_login'), 30, 3);

        // Handle 2FA verification page
        add_action('init', array($this, 'handle_2fa_verification'));

        // Add 2FA verification form to login page
        add_action('login_form', array($this, 'maybe_show_2fa_form'));

        // Process 2FA form submission
        add_action('wp_login', array($this, 'process_2fa_after_login'), 10, 2);

        // Add grace period notices
        add_action('admin_notices', array($this, 'show_grace_period_notices'));

        // Check role enforcement on login
        add_action('wp_login', array($this, 'check_role_enforcement'), 5, 2);

        // Prevent access for users requiring 2FA setup
        add_action('init', array($this, 'enforce_2fa_setup'), 1);
    }

    /**
     * Check if 2FA system is enabled globally
     *
     * @return bool
     */
    public function is_2fa_system_enabled() {
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
     * Intercept WordPress authentication to handle 2FA
     *
     * @param WP_User|WP_Error|null $user User object or error
     * @param string $username Username
     * @param string $password Password
     * @return WP_User|WP_Error
     */
    public function intercept_login($user, $username, $password) {
        // Only process successful authentications
        if (is_wp_error($user) || !$user) {
            return $user;
        }

        // Skip 2FA if already on 2FA verification page
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for route detection, no form processing
        if (isset($_GET['action']) && sanitize_text_field(wp_unslash($_GET['action'])) === 'keyless-2fa-verify') {
            return $user;
        }

        // Skip 2FA for application password authentication
        // Application passwords are already a separate secure authentication method
        if (wp_is_application_passwords_available() && WP_Application_Passwords::is_in_use($user->ID)) {
            // Check if current request is using an application password
            if ($this->is_application_password_request()) {
                return $user;
            }
        }

        // Check if user needs 2FA
        if ($this->user_needs_2fa($user->ID)) {
            // Store user info in session for 2FA verification
            if (!session_id()) {
                session_start();
            }

            $_SESSION['chrmrtns_kla_2fa_user_id'] = $user->ID;
            $_SESSION['chrmrtns_kla_2fa_redirect'] = $this->get_login_redirect_url();

            // Check if user has 2FA set up
            if (!$this->user_has_2fa($user->ID) && $this->user_role_requires_2fa($user->ID)) {
                // Redirect to 2FA setup if role requires but not set up
                wp_redirect(wp_login_url() . '?action=keyless-2fa-setup&user_id=' . $user->ID);
                exit;
            }

            // Redirect to 2FA verification page
            wp_redirect(wp_login_url() . '?action=keyless-2fa-verify');
            exit;
        }

        return $user;
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
     * Process 2FA verification
     *
     * @param int $user_id User ID
     */
    private function process_2fa_verification($user_id) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Called only after nonce verification in show_2fa_verification_page()
        if (!isset($_POST['chrmrtns_2fa_code'])) {
            wp_die(esc_html__('Invalid form submission.', 'keyless-auth'));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Called only after nonce verification in show_2fa_verification_page()
        $code = sanitize_text_field(wp_unslash($_POST['chrmrtns_2fa_code']));
        $is_backup_code = strlen($code) === 8;

        // Verify code
        $valid = false;
        if ($is_backup_code) {
            $valid = $this->database->use_backup_code($user_id, $code);
        } else {
            $settings = $this->database->get_user_2fa_settings($user_id);
            if ($settings && $this->totp->verify_code($code, $settings->totp_secret)) {
                $valid = true;
            }
        }

        if ($valid) {
            // Record successful attempt
            $this->database->record_2fa_attempt($user_id, true);

            // Check if this was a magic link login completion
            $magic_login_data = get_transient('chrmrtns_kla_pending_magic_login_' . $user_id);
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for magic link detection, already within nonce-protected context
            if ($magic_login_data && isset($_GET['magic_login']) && sanitize_text_field(wp_unslash($_GET['magic_login'])) === '1') {
                // Complete magic link login
                wp_set_current_user($user_id);
                wp_set_auth_cookie($user_id, true);

                // Clean up the original magic link token
                delete_user_meta($user_id, 'chrmrtns_kla_login_token');
                delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');

                // Clean up the pending login data
                delete_transient('chrmrtns_kla_pending_magic_login_' . $user_id);

                // Increment successful logins counter
                $current_count = get_option('chrmrtns_kla_successful_logins', 0);
                update_option('chrmrtns_kla_successful_logins', $current_count + 1);

                // Clear session
                unset($_SESSION['chrmrtns_kla_2fa_user_id']);
                unset($_SESSION['chrmrtns_kla_2fa_redirect']);

                // Redirect to the original magic link destination
                wp_redirect($magic_login_data['redirect_url']);
                exit;
            }

            // Normal 2FA completion (not magic link)
            wp_set_current_user($user_id);
            wp_set_auth_cookie($user_id, true);

            // Get redirect URL (custom or session stored)
            if (!empty($_SESSION['chrmrtns_kla_2fa_redirect'])) {
                $redirect_url = esc_url_raw($_SESSION['chrmrtns_kla_2fa_redirect']);
            } else {
                $redirect_url = class_exists('Chrmrtns_KLA_Admin') ? Chrmrtns_KLA_Admin::get_redirect_url($user_id) : admin_url();
            }

            // Clear session
            unset($_SESSION['chrmrtns_kla_2fa_user_id']);
            unset($_SESSION['chrmrtns_kla_2fa_redirect']);

            wp_redirect($redirect_url);
            exit;
        } else {
            // Record failed attempt
            $this->database->record_2fa_attempt($user_id, false);

            // Show error
            add_action('login_head', function() {
                echo '<div class="notice notice-error"><p>' . esc_html__('Invalid verification code. Please try again.', 'keyless-auth') . '</p></div>';
            });
        }
    }

    /**
     * Render 2FA verification form
     *
     * @param WP_User $user User object
     * @param int|false $lockout_seconds Lockout seconds remaining
     */
    private function render_2fa_verification_form($user, $lockout_seconds) {
        // Check if we're in admin context
        if (function_exists('login_header')) {
            // Use WordPress login page styling
            login_header(__('Two-Factor Authentication', 'keyless-auth'), '', '');
        } else {
            // Frontend context - output a full HTML page
            ?>
            <!DOCTYPE html>
            <html <?php language_attributes(); ?>>
            <head>
                <meta charset="<?php bloginfo('charset'); ?>">
                <meta name="viewport" content="width=device-width, initial-scale=1">
                <title><?php esc_html_e('Two-Factor Authentication', 'keyless-auth'); ?> &lsaquo; <?php bloginfo('name'); ?></title>
                <?php wp_head(); ?>
                <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    background: #f1f1f1;
                    margin: 0;
                    padding: 0;
                }
                .chrmrtns-2fa-wrapper {
                    max-width: 400px;
                    margin: 50px auto;
                    background: white;
                    padding: 26px 24px;
                    box-shadow: 0 1px 1px rgba(0,0,0,0.04);
                    border: 1px solid #e5e5e5;
                }
                .chrmrtns-2fa-container .button-primary {
                    background: #0073aa;
                    border-color: #0073aa;
                    color: white;
                    text-decoration: none;
                    display: inline-block;
                    font-size: 13px;
                    line-height: 2.15384615;
                    min-height: 28px;
                    margin: 0;
                    padding: 0 10px;
                    cursor: pointer;
                    border-width: 1px;
                    border-style: solid;
                    border-radius: 3px;
                    white-space: nowrap;
                    box-sizing: border-box;
                }
                </style>
            </head>
            <body>
            <div class="chrmrtns-2fa-wrapper">
            <?php
        }

        ?>
        <div class="chrmrtns-2fa-container">
            <div class="chrmrtns-2fa-header">
                <h2><?php esc_html_e('Two-Factor Authentication Required', 'keyless-auth'); ?></h2>
                <p><?php
                /* translators: %s: user's display name */
                printf(esc_html__('Hi %s, please enter your authenticator code to complete login.', 'keyless-auth'), esc_html($user->display_name)); ?></p>
            </div>

            <?php if ($lockout_seconds): ?>
                <div class="notice notice-error">
                    <p><?php
                    /* translators: %s: formatted time duration (e.g., "5 minutes") */
                    printf(esc_html__('Too many failed attempts. Please try again in %s.', 'keyless-auth'), esc_html($this->totp->format_lockout_time($lockout_seconds))); ?></p>
                </div>
            <?php else: ?>
                <form method="post" id="chrmrtns-2fa-form">
                    <?php wp_nonce_field('chrmrtns_2fa_verify', 'chrmrtns_2fa_nonce'); ?>

                    <p>
                        <label for="chrmrtns_2fa_code"><?php esc_html_e('Verification Code', 'keyless-auth'); ?></label>
                        <input type="text" name="chrmrtns_2fa_code" id="chrmrtns_2fa_code" class="input"
                               maxlength="8" autocomplete="off" required
                               placeholder="<?php esc_attr_e('Enter 6-digit code or 8-digit backup code', 'keyless-auth'); ?>">
                    </p>

                    <p class="submit">
                        <input type="submit" class="button-primary"
                               value="<?php esc_attr_e('Verify', 'keyless-auth'); ?>">
                    </p>
                </form>

                <p class="chrmrtns-2fa-help">
                    <small><?php esc_html_e('Open your authenticator app and enter the 6-digit code, or use one of your 8-digit backup codes.', 'keyless-auth'); ?></small>
                </p>
            <?php endif; ?>

            <p class="chrmrtns-2fa-back">
                <a href="<?php echo esc_url(class_exists('Chrmrtns_KLA_Admin') ? Chrmrtns_KLA_Admin::get_login_url() : wp_login_url()); ?>"><?php esc_html_e('← Back to login', 'keyless-auth'); ?></a>
            </p>
        </div>

        <style>
            .chrmrtns-2fa-container {
                background: white;
                padding: 20px;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                max-width: 400px;
                margin: 20px auto;
            }
            .chrmrtns-2fa-header h2 {
                margin-top: 0;
                color: #23282d;
            }
            .chrmrtns-2fa-help {
                color: #666;
                font-style: italic;
            }
            .chrmrtns-2fa-back {
                text-align: center;
                margin-top: 20px;
            }
            #chrmrtns_2fa_code {
                width: 100%;
                font-size: 18px;
                text-align: center;
                letter-spacing: 2px;
            }
        </style>

        <?php
        if (function_exists('login_footer')) {
            login_footer();
        } else {
            // Frontend context - close HTML
            ?>
            </div> <!-- .chrmrtns-2fa-wrapper -->
            <?php wp_footer(); ?>
            </body>
            </html>
            <?php
        }
        exit;
    }

    /**
     * Show 2FA setup page for required users
     */
    private function show_2fa_setup_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for user setup page, capability check provides protection
        if (!isset($_GET['user_id']) || !current_user_can('read')) {
            wp_redirect(wp_login_url());
            exit;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for user setup page, capability check provides protection
        $user_id = intval(sanitize_text_field(wp_unslash($_GET['user_id'])));
        $user = get_user_by('id', $user_id);

        if (!$user || !$this->user_role_requires_2fa($user_id)) {
            wp_redirect(wp_login_url());
            exit;
        }

        login_header(__('Two-Factor Authentication Setup Required', 'keyless-auth'), '', '');

        ?>
        <div class="chrmrtns-2fa-container">
            <div class="chrmrtns-2fa-header">
                <h2><?php esc_html_e('Two-Factor Authentication Setup Required', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Your account role requires two-factor authentication. Please set up 2FA to continue.', 'keyless-auth'); ?></p>
            </div>

            <div class="chrmrtns-2fa-setup-notice">
                <p><strong><?php esc_html_e('You must set up 2FA to access your account.', 'keyless-auth'); ?></strong></p>
                <p><?php
                /* translators: %s: shortcode name in code tags */
                printf(esc_html__('Use the shortcode %s on the frontend or contact an administrator for assistance.', 'keyless-auth'), '<code>[keyless-auth-2fa]</code>'); ?></p>
            </div>

            <p class="chrmrtns-2fa-back">
                <a href="<?php echo esc_url(wp_logout_url(wp_login_url())); ?>"><?php esc_html_e('← Logout and return to login', 'keyless-auth'); ?></a>
            </p>
        </div>

        <style>
            .chrmrtns-2fa-setup-notice {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                padding: 15px;
                border-radius: 5px;
                margin: 20px 0;
            }
            .chrmrtns-2fa-setup-notice p:last-child {
                margin-bottom: 0;
            }
        </style>

        <?php
        login_footer();
        exit;
    }

    /**
     * Get login redirect URL
     *
     * @return string
     */
    private function get_login_redirect_url() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading redirect parameter, no form processing
        if (isset($_REQUEST['redirect_to'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading redirect parameter, no form processing
            return sanitize_url(wp_unslash($_REQUEST['redirect_to']));
        }

        return admin_url();
    }

    /**
     * Show grace period notices in admin
     */
    public function show_grace_period_notices() {
        if (!current_user_can('read')) {
            return;
        }

        $user_id = get_current_user_id();

        // Only show for users whose role requires 2FA but haven't set it up
        if (!$this->user_role_requires_2fa($user_id) || $this->user_has_2fa($user_id)) {
            return;
        }

        // Calculate grace period remaining
        $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
        $user_registered = get_user_by('id', $user_id)->user_registered;
        $grace_end = strtotime($user_registered . ' + ' . $grace_days . ' days');
        $days_remaining = ceil(($grace_end - time()) / DAY_IN_SECONDS);

        if ($days_remaining > 0) {
            $message = get_option('chrmrtns_kla_2fa_grace_message',
                __('Your account requires 2FA setup within {days} days for security.', 'keyless-auth'));
            $message = str_replace('{days}', $days_remaining, $message);

            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>' . esc_html__('Two-Factor Authentication Required', 'keyless-auth') . '</strong></p>';
            echo '<p>' . esc_html($message) . '</p>';
            /* translators: %s: shortcode name in code tags */
            echo '<p><em>' . sprintf(esc_html__('Use the shortcode %s to set up 2FA.', 'keyless-auth'), '<code>[keyless-auth-2fa]</code>') . '</em></p>';
            echo '</div>';
        }
    }

    /**
     * Enforce 2FA setup for required users after grace period
     */
    public function enforce_2fa_setup() {
        if (!is_admin() || !current_user_can('read')) {
            return;
        }

        $user_id = get_current_user_id();

        // Only enforce for users whose role requires 2FA but haven't set it up
        if (!$this->user_role_requires_2fa($user_id) || $this->user_has_2fa($user_id)) {
            return;
        }

        // Check if grace period has expired
        $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
        $user_registered = get_user_by('id', $user_id)->user_registered;
        $grace_end = strtotime($user_registered . ' + ' . $grace_days . ' days');

        if (time() > $grace_end) {
            // Force logout and redirect to setup
            wp_logout();
            wp_redirect(wp_login_url() . '?action=keyless-2fa-setup&user_id=' . $user_id . '&expired=1');
            exit;
        }
    }

    /**
     * Get 2FA statistics for admin
     *
     * @return array
     */
    public function get_2fa_statistics() {
        global $wpdb;

        $stats = array();

        // Total users with 2FA enabled
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Statistics query for custom table
        $stats['users_with_2fa'] = $wpdb->get_var(
            "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}kla_user_devices WHERE totp_enabled = 1"
        );

        // Total 2FA logins this month
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Statistics query for custom table
        $stats['2fa_logins_this_month'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->prefix}kla_user_devices
             WHERE totp_last_used >= DATE_FORMAT(NOW(), '%Y-%m-01')
             AND totp_last_used IS NOT NULL"
        );

        return $stats;
    }

    /**
     * Check if current request is using an application password
     *
     * @return bool True if using application password, false otherwise
     */
    private function is_application_password_request() {
        // Check for REST API requests (common application password use case)
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }

        // Check for XML-RPC requests (another common application password use case)
        if (defined('XMLRPC_REQUEST') && XMLRPC_REQUEST) {
            return true;
        }

        // Check for WP-CLI requests
        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }

        // Check for specific REST API URL patterns
        $rest_prefix = rest_get_url_prefix();
        if (isset($_SERVER['REQUEST_URI']) && strpos(sanitize_url(wp_unslash($_SERVER['REQUEST_URI'])), '/' . $rest_prefix . '/') !== false) {
            return true;
        }

        // Check for application password authentication header
        if (isset($_SERVER['PHP_AUTH_USER']) || isset($_SERVER['HTTP_AUTHORIZATION'])) {
            // This indicates HTTP Basic Auth which is typically used with application passwords
            // when accessing WordPress programmatically
            return true;
        }

        // Check if we're in an AJAX context that might be using application passwords
        if (wp_doing_ajax() && isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return true;
        }

        return false;
    }

    /**
     * Process 2FA after login - handler for wp_login action
     *
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public function process_2fa_after_login($user_login, $user) {
        // Skip if 2FA system not enabled
        if (!$this->is_2fa_system_enabled()) {
            return;
        }

        // Skip if user doesn't need 2FA
        if (!$this->user_needs_2fa($user->ID)) {
            return;
        }

        // This method can be used for post-login processing
        // Currently, 2FA verification tracking is handled in process_2fa_verification
        // This hook is available for future enhancements
    }

    /**
     * Check role enforcement on login
     *
     * @param string $user_login Username
     * @param WP_User $user User object
     */
    public function check_role_enforcement($user_login, $user) {
        // Skip if 2FA system not enabled
        if (!$this->is_2fa_system_enabled()) {
            return;
        }

        // Skip if user doesn't need 2FA
        if (!$this->user_needs_2fa($user->ID)) {
            return;
        }

        // Check if user role requires 2FA but user hasn't set it up
        if ($this->user_role_requires_2fa($user->ID) && !$this->user_has_2fa($user->ID)) {
            // Check grace period
            $grace_days = intval(get_option('chrmrtns_kla_2fa_grace_period', 10));
            $user_registered = $user->user_registered;
            $grace_end = strtotime($user_registered . ' + ' . $grace_days . ' days');

            if (time() > $grace_end) {
                // Grace period expired - this will be handled by the intercept_login method
                return;
            }
        }
    }

    /**
     * Maybe show 2FA form on login page (placeholder method)
     */
    public function maybe_show_2fa_form() {
        // This method is intentionally empty
        // 2FA verification is handled via redirect to custom pages
        // This hook is kept for potential future use
    }
}