<?php
/**
 * WP-Login Integration Class
 *
 * Handles integration with WordPress default wp-login.php page for Keyless Auth plugin.
 *
 * @package Keyless_Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Frontend;

use Chrmrtns\KeylessAuth\Security\SecurityManager;

/**
 * WpLoginIntegration class
 *
 * Manages wp-login.php integration including:
 * - Adding magic login form to wp-login.php
 * - Handling magic login submissions from wp-login.php
 * - Redirecting wp-login.php to custom login pages
 * - Handling failed login redirects
 */
class WpLoginIntegration {

    /**
     * Security Manager instance
     *
     * @var SecurityManager
     */
    private $security_manager;

    /**
     * Login request handler callback
     *
     * @var callable
     */
    private $login_request_handler;

    /**
     * Constructor
     *
     * @param SecurityManager $security_manager       Optional SecurityManager instance.
     * @param callable        $login_request_handler Callback to handle login requests.
     */
    public function __construct($security_manager = null, $login_request_handler = null) {
        $this->security_manager = $security_manager;
        $this->login_request_handler = $login_request_handler;
    }

    /**
     * Add magic login field to wp-login.php
     *
     * Renders a magic link login form positioned after the main login form.
     *
     * @return void
     */
    public function add_wp_login_field() {
        // Check emergency disable first
        if ($this->security_manager && $this->security_manager->is_emergency_disabled()) {
            return;
        }

        // Only add if the option is enabled
        if (get_option('chrmrtns_kla_enable_wp_login', '0') !== '1') {
            return;
        }

        // Show success message if redirected after sending
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Just checking display parameter, not processing form data
        if (isset($_GET['chrmrtns_kla_sent']) && sanitize_text_field(wp_unslash($_GET['chrmrtns_kla_sent'])) === '1') {
            ?>
            <div style="max-width: 320px; margin: 30px auto 40px auto; padding: 20px; border: 2px solid #00a32a; border-radius: 6px; background: #f0f6fc; color: #00a32a; text-align: center;">
                <p style="margin: 0; font-weight: bold; font-size: 16px;">
                    <?php esc_html_e('Magic login link sent! Check your email and click the link to login.', 'keyless-auth'); ?>
                </p>
            </div>
            <?php
            return;
        }

        ?>
        <div style="max-width: 320px; margin: 30px auto 40px auto; padding: 20px; border: 2px solid #0073aa; border-radius: 6px; background: #f7f9fc;">
            <h3 style="margin: 0 0 12px 0; font-size: 16px; color: #0073aa; text-align: center; font-weight: 600;">
                <?php esc_html_e('🔐 Magic Login', 'keyless-auth'); ?>
            </h3>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #555; text-align: center;">
                <?php esc_html_e('No password required', 'keyless-auth'); ?>
            </p>

            <form method="post" action="<?php echo esc_url(add_query_arg('chrmrtns_kla_magic_request', '1', isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '')); ?>" style="margin: 0;">
                <?php wp_nonce_field('chrmrtns_kla_wp_login', 'chrmrtns_kla_wp_login_nonce'); ?>
                <input type="hidden" name="chrmrtns_kla_wp_login_request" value="1" />

                <label for="chrmrtns_kla_magic_email" style="display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 14px;">
                    <?php esc_html_e('Email or Username:', 'keyless-auth'); ?>
                </label>
                <input type="text"
                       id="chrmrtns_kla_magic_email"
                       name="chrmrtns_kla_magic_email"
                       class="input"
                       size="20"
                       style="width: 100%; padding: 8px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box;"
                       placeholder="<?php esc_attr_e('Enter email or username', 'keyless-auth'); ?>"
                       required />

                <input type="submit"
                       name="chrmrtns_kla_wp_login_submit"
                       class="button button-primary button-large"
                       value="<?php esc_attr_e('Send Magic Link', 'keyless-auth'); ?>"
                       style="width: 100%; padding: 10px; font-size: 14px;" />
            </form>
        </div>

        <style>
        body.login {
            padding-bottom: 60px;
        }
        </style>
        <?php
    }

    /**
     * Handle magic login request from wp-login.php
     *
     * Processes form submission and sends magic link email.
     *
     * @return void Exits after redirect.
     */
    public function handle_wp_login_submission() {
        // Check if emergency disabled - return early if so
        if ($this->security_manager && $this->security_manager->is_emergency_disabled()) {
            return;
        }

        // Only handle if this is our magic login request (check both POST and GET)
        if (!isset($_GET['chrmrtns_kla_magic_request']) ||
            !isset($_POST['chrmrtns_kla_wp_login_request']) ||
            !isset($_POST['chrmrtns_kla_wp_login_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_wp_login_nonce'])), 'chrmrtns_kla_wp_login')) {
            return;
        }

        // Get the email/username from our custom field
        if (!isset($_POST['chrmrtns_kla_magic_email'])) {
            return;
        }
        $user_input = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_magic_email']));

        if (empty($user_input)) {
            wp_die(esc_html__('Please enter your email address or username.', 'keyless-auth'));
        }

        // Process the magic login request (reuse existing logic)
        // Set the POST data that handle_login_request expects
        $_POST['user_email_username'] = $user_input;
        $_POST['nonce'] = wp_create_nonce('chrmrtns_kla_keyless_login_request');

        // Call the login request handler callback
        if (is_callable($this->login_request_handler)) {
            call_user_func($this->login_request_handler);
        }

        // Redirect back to wp-login.php with success message
        wp_safe_redirect(add_query_arg(array(
            'chrmrtns_kla_sent' => '1'
        ), wp_login_url()));
        exit;
    }

    /**
     * Maybe redirect wp-login.php to custom login page
     *
     * Redirects users from wp-login.php to a custom login page if configured.
     *
     * @return void Exits after redirect if applicable.
     */
    public function maybe_redirect_wp_login() {
        // Only check if we're in the right context
        if (!isset($_SERVER['REQUEST_URI']) || !isset($_SERVER['SCRIPT_NAME'])) {
            return;
        }

        // Check if redirect is enabled
        $redirect_enabled = get_option('chrmrtns_kla_redirect_wp_login', '0');
        if ($redirect_enabled !== '1') {
            return;
        }

        // Check if custom login URL is configured
        $custom_login_url = get_option('chrmrtns_kla_custom_login_url', '');
        if (empty($custom_login_url)) {
            return;
        }

        // Check for bypass parameter
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter used for admin bypass, no security impact
        if (isset($_GET['kla_use_wp_login']) && $_GET['kla_use_wp_login'] === '1') {
            return;
        }

        // Check if this is a request to wp-login.php
        $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
        $script_name = sanitize_text_field(wp_unslash($_SERVER['SCRIPT_NAME']));

        // Check if we're accessing wp-login.php
        if (strpos($script_name, 'wp-login.php') === false && strpos($request_uri, 'wp-login.php') === false) {
            return;
        }

        // Don't redirect if this is a login form submission (POST request)
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            return;
        }

        // Preserve important actions - don't redirect these
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter used for action detection, no security impact
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
        $preserve_actions = array('logout', 'lostpassword', 'resetpass', 'rp', 'register');

        if (in_array($action, $preserve_actions, true)) {
            return;
        }

        // Preserve error parameters when redirecting
        $redirect_url = $custom_login_url;
        $params_to_preserve = array();

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for error display, no security impact
        if (isset($_GET['login'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters from WordPress redirect
            $params_to_preserve['login'] = sanitize_text_field(wp_unslash($_GET['login']));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for error display, no security impact
        if (isset($_GET['error'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters from WordPress redirect
            $params_to_preserve['login_error'] = sanitize_text_field(wp_unslash($_GET['error']));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for success messages, no security impact
        if (isset($_GET['loggedout'])) {
            $params_to_preserve['loggedout'] = 'true';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for success messages, no security impact
        if (isset($_GET['registered'])) {
            $params_to_preserve['registered'] = 'true';
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for success messages, no security impact
        if (isset($_GET['checkemail'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters from WordPress redirect
            $params_to_preserve['checkemail'] = sanitize_text_field(wp_unslash($_GET['checkemail']));
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters for redirect URL, no security impact
        if (isset($_GET['redirect_to'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameters from WordPress redirect
            $params_to_preserve['redirect_to'] = esc_url_raw(wp_unslash($_GET['redirect_to']));
        }

        // Add preserved parameters to redirect URL
        if (!empty($params_to_preserve)) {
            $redirect_url = add_query_arg($params_to_preserve, $custom_login_url);
        }

        // Perform the redirect with validation
        wp_safe_redirect(wp_validate_redirect($redirect_url, wp_login_url()));
        exit;
    }

    /**
     * Handle failed login attempts
     *
     * Redirects to custom login page with error parameters when authentication fails.
     *
     * @param string    $username Username or email used in login attempt.
     * @param \WP_Error $error    WP_Error object containing error details.
     * @return void Exits after redirect if applicable.
     */
    public function handle_failed_login($username, $error) {
        // Check if custom login redirect is enabled
        $redirect_enabled = get_option('chrmrtns_kla_redirect_wp_login', '0');
        if ($redirect_enabled !== '1') {
            return;
        }

        // Check if custom login URL is configured
        $custom_login_url = get_option('chrmrtns_kla_custom_login_url', '');
        if (empty($custom_login_url)) {
            return;
        }

        // Get the error code from WP_Error object
        $error_code = $error->get_error_code();

        // Build redirect URL with error parameter
        $redirect_url = add_query_arg('login_error', $error_code, $custom_login_url);

        // Also preserve the username for better UX (optional)
        if (!empty($username)) {
            $redirect_url = add_query_arg('login', sanitize_text_field($username), $redirect_url);
        }

        // Perform the redirect with validation
        wp_safe_redirect(wp_validate_redirect($redirect_url, wp_login_url()));
        exit;
    }
}
