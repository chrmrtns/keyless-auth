<?php
/**
 * Custom Password Reset Page for Keyless Auth
 *
 * Creates a custom /reset-password page that replaces wp-login.php?action=lostpassword
 * with a beautiful branded form matching Keyless Auth styling.
 *
 * @package Chrmrtns\KeylessAuth
 * @since 3.2.0
 */

namespace Chrmrtns\KeylessAuth\Core;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class PasswordReset {

    /**
     * Constructor
     */
    public function __construct() {
        // Only add hooks if setting is enabled
        if (!$this->is_enabled()) {
            return;
        }

        // Register shortcode for password reset form
        add_shortcode('keyless-auth-password-reset', array($this, 'render_shortcode'));
    }

    /**
     * Check if custom password reset is enabled
     *
     * @return bool
     */
    private function is_enabled() {
        return get_option('chrmrtns_kla_custom_password_reset', '0') === '1';
    }

    /**
     * Get the login URL (respects custom login URL setting)
     *
     * @return string
     */
    private function get_login_url() {
        $custom_login_url = get_option('chrmrtns_kla_custom_login_url', '');

        if (!empty($custom_login_url)) {
            return $custom_login_url;
        }

        // Fall back to wp-login.php
        return wp_login_url();
    }

    /**
     * Render the shortcode
     *
     * @return string
     */
    public function render_shortcode() {
        // Enqueue styles when shortcode is rendered (includes filter for custom CSS variables)
        $this->enqueue_frontend_scripts();

        ob_start();
        $this->render_reset_password_page();
        return ob_get_clean();
    }

    /**
     * Enqueue frontend scripts and styles
     * Matches the Core class implementation to ensure consistent theming
     */
    private function enqueue_frontend_scripts() {
        // Get dark mode setting
        $dark_mode_setting = get_option('chrmrtns_kla_dark_mode_setting', 'auto');

        // Determine which CSS file to load based on dark mode setting
        $css_file = 'forms-enhanced.css'; // Default: auto mode

        switch ($dark_mode_setting) {
            case 'light':
                $css_file = 'forms-enhanced-light.css';
                break;
            case 'dark':
                $css_file = 'forms-enhanced-dark.css';
                break;
            case 'auto':
            default:
                $css_file = 'forms-enhanced.css';
                break;
        }

        // Enqueue the appropriate enhanced forms stylesheet
        if (file_exists(CHRMRTNS_KLA_PLUGIN_DIR . '/assets/css/' . $css_file)) {
            wp_enqueue_style(
                'chrmrtns_kla_forms_enhanced',
                CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/' . $css_file,
                array(),
                CHRMRTNS_KLA_VERSION,
                'all'
            );

            // Apply the custom CSS variables filter (for theme integration)
            $custom_css = apply_filters('chrmrtns_kla_custom_css_variables', '');
            if (!empty($custom_css)) {
                wp_add_inline_style('chrmrtns_kla_forms_enhanced', $custom_css);
            }
        }
    }

    /**
     * Render the reset password page
     */
    private function render_reset_password_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters, not form submission
        $action = isset($_GET['action']) ? sanitize_text_field(wp_unslash($_GET['action'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters, not form submission
        $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : '';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters, not form submission
        $login = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';

        $error = '';
        $success = '';
        $show_form = false;
        $show_request_form = false;

        // Handle password reset request (send email)
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset_nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['request_reset_nonce'])), 'request_reset_action')) {
                $error = __('Security check failed. Please try again.', 'keyless-auth');
                $show_request_form = true;
            } else {
                $user_email = isset($_POST['user_email']) ? sanitize_email(wp_unslash($_POST['user_email'])) : '';

                if (empty($user_email)) {
                    $error = __('Please enter your email address.', 'keyless-auth');
                    $show_request_form = true;
                } else {
                    // Look up user by email or username
                    if (is_email($user_email)) {
                        $user = get_user_by('email', $user_email);
                    } else {
                        $user = get_user_by('login', $user_email);
                    }

                    if (!$user) {
                        // Don't reveal if user exists or not for security
                        $success = __('If this email is registered, you will receive a password reset link shortly.', 'keyless-auth');
                    } else {
                        // Generate password reset key
                        $key = get_password_reset_key($user);

                        if (is_wp_error($key)) {
                            $error = $key->get_error_message();
                            $show_request_form = true;
                        } else {
                            // Build custom reset URL
                            $custom_reset_url = get_option('chrmrtns_kla_custom_password_reset_url', '');
                            if (!empty($custom_reset_url)) {
                                $reset_url = add_query_arg(array(
                                    'action' => 'rp',
                                    'key' => $key,
                                    'login' => rawurlencode($user->user_login)
                                ), $custom_reset_url);
                            } else {
                                $reset_url = network_site_url("wp-login.php?action=rp&key=$key&login=" . rawurlencode($user->user_login), 'login');
                            }

                            // Send email
                            /* translators: 1: Site name, 2: Username, 3: Password reset URL */
                            $message = sprintf(__('Someone has requested a password reset for the following account:

Site Name: %1$s
Username: %2$s

If this was a mistake, just ignore this email and nothing will happen.

To reset your password, visit the following address:

%3$s', 'keyless-auth'),
                                get_bloginfo('name'),
                                $user->user_login,
                                $reset_url
                            );

                            /* translators: %s: Site name */
                            $subject = sprintf(__('[%s] Password Reset', 'keyless-auth'), get_bloginfo('name'));
                            $headers = array('Content-Type: text/html; charset=UTF-8');

                            if (wp_mail($user->user_email, $subject, nl2br($message), $headers)) {
                                $success = __('Password reset email sent! Please check your inbox.', 'keyless-auth');
                            } else {
                                $error = __('Could not send email. Please contact the site administrator.', 'keyless-auth');
                                $show_request_form = true;
                            }
                        }
                    }
                }
            }
        }
        // Handle password reset submission (with key)
        elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password_nonce'])) {
            if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['reset_password_nonce'])), 'reset_password_action')) {
                $error = __('Security check failed. Please try again.', 'keyless-auth');
            } else {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Passwords should not be sanitized or slashed
                $password = isset($_POST['password']) ? $_POST['password'] : '';
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Passwords should not be sanitized or slashed
                $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';
                $reset_key = isset($_POST['reset_key']) ? sanitize_text_field(wp_unslash($_POST['reset_key'])) : '';
                $reset_login = isset($_POST['reset_login']) ? sanitize_text_field(wp_unslash($_POST['reset_login'])) : '';

                if (empty($password) || empty($password_confirm)) {
                    $error = __('Please enter and confirm your password.', 'keyless-auth');
                    $show_form = true;
                } elseif ($password !== $password_confirm) {
                    $error = __('Passwords do not match. Please try again.', 'keyless-auth');
                    $show_form = true;
                } elseif (strlen($password) < 8) {
                    $error = __('Password must be at least 8 characters long.', 'keyless-auth');
                    $show_form = true;
                } else {
                    // Validate reset key and get user
                    $user = check_password_reset_key($reset_key, $reset_login);

                    if (is_wp_error($user)) {
                        $error = __('Invalid or expired reset link. Please request a new one.', 'keyless-auth');
                    } else {
                        // Reset the password
                        reset_password($user, $password);

                        $success = __('Your password has been reset successfully! Redirecting to login...', 'keyless-auth');

                        // Redirect to login page after 3 seconds
                        echo '<meta http-equiv="refresh" content="3;url=' . esc_url($this->get_login_url()) . '">';
                    }
                }
            }
        } elseif ($action === 'rp' && !empty($key) && !empty($login)) {
            // Validate the reset key
            $user = check_password_reset_key($key, $login);

            if (is_wp_error($user)) {
                $error = __('Invalid or expired reset link. Please request a new password reset.', 'keyless-auth');
                $show_request_form = true;
            } else {
                $show_form = true;
            }
        } else {
            // Show request form (step 1: enter email)
            $show_request_form = true;
        }

        // Render the page
        $this->render_html_template($error, $success, $show_form, $show_request_form, $key, $login);
    }

    /**
     * Render the HTML template for password reset page
     *
     * @param string $error Error message
     * @param string $success Success message
     * @param bool $show_form Show password reset form
     * @param bool $show_request_form Show email request form
     * @param string $key Reset key
     * @param string $login User login
     */
    private function render_html_template($error, $success, $show_form, $show_request_form, $key, $login) {
        ?>
    <style>
        .chrmrtns-password-reset-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            max-width: 500px;
            margin: 0 auto;
        }
        .chrmrtns-password-reset-container * {
            box-sizing: border-box;
        }
        .chrmrtns-password-reset-box {
            background: var(--kla-background, #ffffff);
            padding: 20px;
            border: 1px solid var(--kla-border-light, #dcdcde);
            border-radius: var(--kla-radius, 4px);
            margin-bottom: 20px;
            box-shadow: var(--kla-shadow, 0 1px 3px rgba(0, 0, 0, 0.04));
        }
        .chrmrtns-password-reset-container .reset-header {
            margin: 0 0 20px 0;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--kla-border-light, #dcdcde);
        }
        .chrmrtns-password-reset-container .reset-header h1 {
            margin-top: 0;
            color: var(--kla-text, #2c3338);
            font-size: 24px;
            font-weight: 600;
        }
        .chrmrtns-password-reset-container .reset-header p {
            font-size: 14px;
            color: var(--kla-text-light, #646970);
            margin: 5px 0 0 0;
        }
        .chrmrtns-password-reset-container .message {
            padding: 12px 15px;
            border-radius: var(--kla-radius, 4px);
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.5;
        }
        .chrmrtns-password-reset-container .message.error {
            background: var(--kla-error-light, #f8d7da);
            border-left: 4px solid var(--kla-error, #d63638);
            color: var(--kla-error-text, #721c24);
        }
        .chrmrtns-password-reset-container .message.success {
            background: #d4edda;
            border-left: 4px solid var(--kla-success, #46b450);
            color: var(--kla-success-text, #155724);
        }
        .chrmrtns-password-reset-container .form-description {
            margin-bottom: 20px;
            color: var(--kla-text-light, #646970);
            font-size: 14px;
        }
        .chrmrtns-password-reset-container .form-group {
            margin-bottom: 20px;
        }
        .chrmrtns-password-reset-container .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: var(--kla-text, #2c3338);
            font-size: 14px;
        }
        .chrmrtns-password-reset-container .form-group input[type="password"],
        .chrmrtns-password-reset-container .form-group input[type="email"] {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--kla-border, #8c8f94);
            border-radius: var(--kla-radius, 4px);
            font-size: 14px;
            box-sizing: border-box;
            color: var(--kla-text, #2c3338);
            background: var(--kla-background, #ffffff);
            transition: var(--kla-transition, all 0.2s ease);
        }
        .chrmrtns-password-reset-container .form-group input[type="password"]:focus,
        .chrmrtns-password-reset-container .form-group input[type="email"]:focus {
            outline: none;
            border-color: var(--kla-primary, #0073aa);
            box-shadow: 0 0 0 1px var(--kla-primary, #0073aa);
        }
        .chrmrtns-password-reset-container .password-requirements {
            font-size: 12px;
            color: var(--kla-text-light, #646970);
            margin-top: 5px;
            line-height: 1.5;
        }
        .chrmrtns-password-reset-container .password-requirements li {
            margin-left: 20px;
        }
        .chrmrtns-password-reset-container .submit-button {
            width: 100%;
            padding: 10px 16px;
            background: var(--kla-primary, #0073aa);
            color: #ffffff;
            border: 1px solid var(--kla-primary, #0073aa);
            border-radius: var(--kla-radius, 4px);
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--kla-transition, all 0.2s ease);
        }
        .chrmrtns-password-reset-container .submit-button:hover {
            background: var(--kla-primary-hover, #005a87);
            border-color: var(--kla-primary-hover, #005a87);
        }
        .chrmrtns-password-reset-container .submit-button:active,
        .chrmrtns-password-reset-container .submit-button:focus {
            background: var(--kla-primary-active, #004a70);
            border-color: var(--kla-primary-active, #004a70);
            outline: 2px solid var(--kla-primary, #0073aa);
            outline-offset: 2px;
        }
        .chrmrtns-password-reset-container .back-link {
            text-align: center;
            margin-top: 15px;
            font-size: 14px;
        }
        .chrmrtns-password-reset-container .back-link a {
            color: var(--kla-primary, #0073aa);
            text-decoration: none;
            transition: var(--kla-transition, all 0.2s ease);
        }
        .chrmrtns-password-reset-container .back-link a:hover {
            color: var(--kla-primary-hover, #005a87);
            text-decoration: underline;
        }
        .chrmrtns-password-reset-container .reset-footer {
            background: var(--kla-background-alt, #f6f7f7);
            padding: 15px;
            text-align: center;
            font-size: 13px;
            color: var(--kla-text-light, #646970);
            border: 1px solid var(--kla-border-light, #dcdcde);
            border-radius: var(--kla-radius, 4px);
        }
        .chrmrtns-password-reset-container .reset-footer a {
            color: var(--kla-primary, #0073aa);
            text-decoration: none;
            transition: var(--kla-transition, all 0.2s ease);
        }
        .chrmrtns-password-reset-container .reset-footer a:hover {
            color: var(--kla-primary-hover, #005a87);
        }
    </style>
    <div class="chrmrtns-password-reset-container">
        <div class="chrmrtns-password-reset-box">
            <div class="reset-header">
                <h1><?php esc_html_e('Reset Password', 'keyless-auth'); ?></h1>
                <p><?php bloginfo('name'); ?></p>
            </div>
            <?php if ($error): ?>
                <div class="message error">
                    <strong><?php esc_html_e('Error:', 'keyless-auth'); ?></strong> <?php echo esc_html($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="message success">
                    <strong><?php esc_html_e('Success!', 'keyless-auth'); ?></strong> <?php echo esc_html($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($show_request_form): ?>
                <p class="form-description">
                    <?php esc_html_e('Enter your email address below and we\'ll send you a link to reset your password.', 'keyless-auth'); ?>
                </p>

                <form method="POST" action="">
                    <?php wp_nonce_field('request_reset_action', 'request_reset_nonce'); ?>

                    <div class="form-group">
                        <label for="user_email"><?php esc_html_e('Email Address', 'keyless-auth'); ?></label>
                        <input
                            type="email"
                            id="user_email"
                            name="user_email"
                            required
                            placeholder="<?php esc_attr_e('Enter your email address', 'keyless-auth'); ?>"
                            autocomplete="email"
                        >
                    </div>

                    <button type="submit" class="submit-button"><?php esc_html_e('Send Reset Link', 'keyless-auth'); ?></button>
                </form>
            <?php endif; ?>

            <?php if ($show_form): ?>
                <form method="POST" action="">
                    <?php wp_nonce_field('reset_password_action', 'reset_password_nonce'); ?>
                    <input type="hidden" name="reset_key" value="<?php echo esc_attr($key); ?>">
                    <input type="hidden" name="reset_login" value="<?php echo esc_attr($login); ?>">

                    <div class="form-group">
                        <label for="password"><?php esc_html_e('New Password', 'keyless-auth'); ?></label>
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            minlength="8"
                            placeholder="<?php esc_attr_e('Enter your new password', 'keyless-auth'); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="password_confirm"><?php esc_html_e('Confirm New Password', 'keyless-auth'); ?></label>
                        <input
                            type="password"
                            id="password_confirm"
                            name="password_confirm"
                            required
                            minlength="8"
                            placeholder="<?php esc_attr_e('Confirm your new password', 'keyless-auth'); ?>"
                        >
                        <div class="password-requirements">
                            <strong><?php esc_html_e('Password requirements:', 'keyless-auth'); ?></strong>
                            <ul>
                                <li><?php esc_html_e('At least 8 characters long', 'keyless-auth'); ?></li>
                                <li><?php esc_html_e('Recommended: Mix of letters, numbers, and symbols', 'keyless-auth'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <button type="submit" class="submit-button"><?php esc_html_e('Reset Password', 'keyless-auth'); ?></button>
                </form>
            <?php endif; ?>

            <div class="back-link">
                <a href="<?php echo esc_url($this->get_login_url()); ?>">← <?php esc_html_e('Back to Login', 'keyless-auth'); ?></a>
            </div>
        </div>

        <?php
        // Optional: Show support link if configured
        $support_url = get_option('chrmrtns_kla_support_url', '');
        if (!empty($support_url)) :
        ?>
        <div class="reset-footer">
            <?php esc_html_e('Need help?', 'keyless-auth'); ?> <a href="<?php echo esc_url($support_url); ?>"><?php esc_html_e('Contact Support', 'keyless-auth'); ?></a>
        </div>
        <?php endif; ?>
    </div>
        <?php
    }
}
