<?php
/**
 * Login Form Renderer Class
 *
 * Handles rendering of login forms for Keyless Auth plugin.
 *
 * @package Keyless_Auth
 * @since 3.3.0
 */

namespace Chrmrtns\KeylessAuth\Frontend;

use Chrmrtns\KeylessAuth\Core\UrlHelper;

/**
 * LoginFormRenderer class
 *
 * Manages login form rendering including:
 * - Simple magic link login forms
 * - Full login forms with both password and magic link options
 * - Status messages (success, error, logged in state)
 * - Profile Builder integration for custom labels
 */
class LoginFormRenderer {

    /**
     * Render status messages for login forms
     *
     * @param bool         $account       Whether an email was sent.
     * @param \WP_Error|bool $sent_link    Result of email sending.
     * @param string|bool  $error_token   Error token from URL.
     * @param string|bool  $adminapp_error Admin approval error.
     * @return void
     */
    public static function renderStatusMessages($account, $sent_link, $error_token = false, $adminapp_error = false) {
        // Success message for magic link sent
        if ($account && !is_wp_error($sent_link)) {
            echo '<p class="chrmrtns-box chrmrtns-success" role="status" aria-live="polite">' . wp_kses_post(apply_filters('chrmrtns_kla_success_link_msg', esc_html__('Please check your email. You will soon receive an email with a login link.', 'keyless-auth'))) . '</p>';
        } elseif (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            echo '<p class="chrmrtns-box chrmrtns-alert" role="status" aria-live="polite">' . wp_kses_post(apply_filters('chrmrtns_kla_success_login_msg', sprintf(
                /* translators: %1$s: user display name with link, %2$s: logout link */
                esc_html__('You are currently logged in as %1$s. %2$s', 'keyless-auth'),
                '<a href="' . esc_url(get_author_posts_url($current_user->ID)) . '" title="' . esc_attr($current_user->display_name) . '">' . esc_html($current_user->display_name) . '</a>',
                '<a href="' . esc_url(wp_logout_url(UrlHelper::getCurrentPageUrl())) . '" title="' . esc_html__('Log out of this account', 'keyless-auth') . '">' . esc_html__('Log out', 'keyless-auth') . ' &raquo;</a>'
            ))) . '</p><!-- .alert-->';
        } else {
            // Error messages
            if (is_wp_error($sent_link)) {
                echo '<p class="chrmrtns-box chrmrtns-error" role="alert" aria-live="assertive">' . esc_html(apply_filters('chrmrtns_error', $sent_link->get_error_message())) . '</p>';
            }
            if ($error_token) {
                echo '<p class="chrmrtns-box chrmrtns-error" role="alert" aria-live="assertive">' . wp_kses_post(apply_filters('chrmrtns_kla_invalid_token_error', __('Your token has probably expired. Please try again.', 'keyless-auth'))) . '</p>';
            }
            if ($adminapp_error) { // admin approval compatibility
                echo '<p class="chrmrtns-box chrmrtns-error" role="alert" aria-live="assertive">' . wp_kses_post(apply_filters('chrmrtns_kla_admin_approval_error', __('Your account needs to be approved by an admin before you can log-in.', 'keyless-auth'))) . '</p>';
            }

            // Show WordPress native login errors (from wp-login.php redirects)
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
            $login_error = isset($_GET['login_error']) ? sanitize_text_field(wp_unslash($_GET['login_error'])) : '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
            $login_status = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';

            if ($login_error || $login_status) {
                $error_message = MessageFormatter::getLoginErrorMessage($login_error, $login_status);
                if ($error_message) {
                    echo '<p class="chrmrtns-box chrmrtns-error" role="alert">' . wp_kses_post($error_message) . '</p>';
                }
            }
        }
    }

    /**
     * Render status messages for full login form (with additional message types)
     *
     * @param bool         $account       Whether an email was sent.
     * @param \WP_Error|bool $sent_link    Result of email sending.
     * @param string|bool  $error_token   Error token from URL.
     * @param string|bool  $adminapp_error Admin approval error.
     * @return void
     */
    public static function renderFullFormStatusMessages($account, $sent_link, $error_token = false, $adminapp_error = false) {
        // Success message for magic link
        if ($account && !is_wp_error($sent_link)) {
            echo '<p class="chrmrtns-box chrmrtns-success" role="status" aria-live="polite">' . wp_kses_post(apply_filters('chrmrtns_kla_success_link_msg', esc_html__('Please check your email. You will soon receive an email with a login link.', 'keyless-auth'))) . '</p>';
        } elseif (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            echo '<p class="chrmrtns-box chrmrtns-alert" role="status" aria-live="polite">' . wp_kses_post(apply_filters('chrmrtns_kla_success_login_msg', sprintf(
                /* translators: %1$s: user display name with link, %2$s: logout link */
                esc_html__('You are currently logged in as %1$s. %2$s', 'keyless-auth'),
                '<a href="' . esc_url(get_author_posts_url($current_user->ID)) . '" title="' . esc_attr($current_user->display_name) . '">' . esc_html($current_user->display_name) . '</a>',
                '<a href="' . esc_url(wp_logout_url(UrlHelper::getCurrentPageUrl())) . '" title="' . esc_html__('Log out of this account', 'keyless-auth') . '">' . esc_html__('Log out', 'keyless-auth') . ' &raquo;</a>'
            ))) . '</p>';
        } else {
            // Show error messages
            if ($error_token && $error_token === 'expired') {
                echo '<p class="chrmrtns-box chrmrtns-error">' . esc_html(apply_filters('chrmrtns_kla_token_expired_text', __('Your login link has expired. Please request a new one.', 'keyless-auth'))) . '</p>';
            } elseif ($error_token && $error_token === 'invalid') {
                echo '<p class="chrmrtns-box chrmrtns-error">' . wp_kses_post(apply_filters('chrmrtns_kla_token_invalid_text', __('Your login link is invalid. Please request a new one.', 'keyless-auth'))) . '</p>';
            } elseif ($adminapp_error && $adminapp_error === 'failed') {
                echo '<p class="chrmrtns-box chrmrtns-error">' . wp_kses_post(apply_filters('chrmrtns_kla_admin_app_failed_text', __('Login failed. Please try again.', 'keyless-auth'))) . '</p>';
            } elseif (is_wp_error($sent_link)) {
                echo '<p class="chrmrtns-box chrmrtns-error">' . wp_kses_post(apply_filters('chrmrtns_kla_error_send_link_msg', __('Email could not be sent. Please try again.', 'keyless-auth'))) . '</p>';
            }

            // Show WordPress native login errors (from wp-login.php redirects)
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
            $login_error = isset($_GET['login_error']) ? sanitize_text_field(wp_unslash($_GET['login_error'])) : '';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
            $login_status = isset($_GET['login']) ? sanitize_text_field(wp_unslash($_GET['login'])) : '';

            if ($login_error || $login_status) {
                $error_message = MessageFormatter::getLoginErrorMessage($login_error, $login_status);
                if ($error_message) {
                    echo '<p class="chrmrtns-box chrmrtns-error" role="alert">' . wp_kses_post($error_message) . '</p>';
                }
            }

            // Show success messages
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for success messages
            if (isset($_GET['loggedout']) && $_GET['loggedout'] === 'true') {
                echo '<p class="chrmrtns-box chrmrtns-success" role="status">' . esc_html__('You have successfully logged out.', 'keyless-auth') . '</p>';
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for success messages
            if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
                echo '<p class="chrmrtns-box chrmrtns-success" role="status">' . esc_html__('Registration complete. Please check your email.', 'keyless-auth') . '</p>';
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for success messages
            $checkemail = isset($_GET['checkemail']) ? sanitize_text_field(wp_unslash($_GET['checkemail'])) : '';
            if ($checkemail === 'confirm') {
                echo '<p class="chrmrtns-box chrmrtns-success" role="status">' . esc_html__('Check your email for the confirmation link.', 'keyless-auth') . '</p>';
            } elseif ($checkemail === 'newpass') {
                echo '<p class="chrmrtns-box chrmrtns-success" role="status">' . esc_html__('Check your email for your new password.', 'keyless-auth') . '</p>';
            }
        }
    }

    /**
     * Get login label based on Profile Builder settings
     *
     * @return string Login label text.
     */
    public static function getLoginLabel() {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        // Setting up the label for the password request form based on Profile Builder Option
        $login_label = __('Login with email or username', 'keyless-auth');

        if (is_plugin_active('profile-builder-pro/index.php') || is_plugin_active('profile-builder/index.php') || is_plugin_active('profile-builder-hobbyist/index.php')) {
            $wppb_general_options = get_option('wppb_general_settings');
            if (isset($wppb_general_options['loginWith']) && ($wppb_general_options['loginWith'] == 'email')) {
                $login_label = __('Login with email', 'keyless-auth');
            } elseif (isset($wppb_general_options['loginWith']) && ($wppb_general_options['loginWith'] == 'username')) {
                $login_label = __('Login with username', 'keyless-auth');
            }
        }

        return $login_label;
    }

    /**
     * Render simple magic link login form HTML
     *
     * @param array $atts Shortcode attributes.
     * @return void
     */
    public static function renderLoginFormHtml($atts = array()) {
        $login_label = self::getLoginLabel();

        // Determine label text
        $label_text = !empty($atts['label']) ? $atts['label'] : $login_label;

        // Determine button text
        $button_text = !empty($atts['button_text']) ? $atts['button_text'] : __('Send me the link', 'keyless-auth');

        ?>
        <div class="chrmrtns-kla-form-wrapper">
            <?php if (!empty($atts['description'])): ?>
                <p class="chrmrtns-kla-description"><?php echo wp_kses_post($atts['description']); ?></p>
            <?php endif; ?>
            <form method="post" class="chrmrtns-form" aria-label="<?php esc_attr_e('Magic link login form', 'keyless-auth'); ?>">
                <p>
                    <label for="user_email_username"><?php echo esc_html(apply_filters('chrmrtns_kla_change_form_label', $label_text)); ?></label><br>
                    <input type="text" name="user_email_username" id="user_email_username" class="input" value="" size="20" required aria-required="true" />
                </p>
                <?php wp_nonce_field('chrmrtns_kla_keyless_login_request', 'nonce', false); ?>
                <input type="hidden" name="chrmrtns_kla_magic_form" value="1" />
                <?php if (!empty($atts['redirect'])): ?>
                    <input type="hidden" name="chrmrtns_kla_redirect" value="<?php echo esc_url($atts['redirect']); ?>" />
                <?php endif; ?>
                <p class="submit">
                    <input type="submit" name="wp-submit" id="chrmrtns-submit" class="button-primary" value="<?php echo esc_attr($button_text); ?>" />
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render full login form HTML with both standard and magic link options
     *
     * @param array $atts Shortcode attributes.
     * @return void
     */
    public static function renderFullLoginFormHtml($atts = array()) {
        $login_label = self::getLoginLabel();
        $redirect_to = !empty($atts['redirect']) ? esc_url($atts['redirect']) : UrlHelper::getCurrentPageUrl();
        ?>
        <div class="chrmrtns-kla-form-wrapper">
        <div class="chrmrtns-full-login-container">
            <!-- Standard WordPress Login Form -->
            <div class="chrmrtns-standard-login" data-form-type="password" role="region" aria-label="<?php esc_attr_e('Password login', 'keyless-auth'); ?>">
                <h4><?php esc_html_e('Login with Password', 'keyless-auth'); ?></h4>
                <?php
                wp_login_form(array(
                    'redirect' => $redirect_to,
                    'form_id' => 'chrmrtns_standard_loginform',
                    'label_username' => __('Username or Email', 'keyless-auth'),
                    'label_password' => __('Password', 'keyless-auth'),
                    'label_remember' => __('Remember Me', 'keyless-auth'),
                    'label_log_in' => __('Log In', 'keyless-auth'),
                    'id_username' => 'chrmrtns_user_login',
                    'id_password' => 'chrmrtns_user_pass',
                    'id_remember' => 'chrmrtns_rememberme',
                    'id_submit' => 'chrmrtns_wp-submit',
                    'remember' => true,
                    'value_username' => '',
                    'value_remember' => false
                ));
                ?>
                <p class="chrmrtns-forgot-password">
                    <?php
                    // Use custom reset page if enabled, otherwise use default wp-login.php
                    $use_custom_reset = get_option('chrmrtns_kla_custom_password_reset', '0') === '1';
                    $custom_reset_url = get_option('chrmrtns_kla_custom_password_reset_url', '');

                    if ($use_custom_reset && !empty($custom_reset_url)) {
                        $reset_url = $custom_reset_url;
                    } else {
                        $reset_url = wp_lostpassword_url($redirect_to);
                    }
                    ?>
                    <a href="<?php echo esc_url($reset_url); ?>"><?php esc_html_e('Forgot your password?', 'keyless-auth'); ?></a>
                </p>
            </div>

            <div class="chrmrtns-login-separator">
                <span><?php esc_html_e('OR', 'keyless-auth'); ?></span>
            </div>

            <!-- Magic Link Login Form -->
            <div class="chrmrtns-magic-login" data-form-type="magic" role="region" aria-label="<?php esc_attr_e('Magic link login', 'keyless-auth'); ?>">
                <h4><?php esc_html_e('Magic Link Login', 'keyless-auth'); ?></h4>
                <p class="chrmrtns-magic-description"><?php esc_html_e('No password required - we\'ll send you a secure login link via email.', 'keyless-auth'); ?></p>
                <form method="post" class="chrmrtns-form" aria-label="<?php esc_attr_e('Magic link login form', 'keyless-auth'); ?>">
                    <p>
                        <label for="user_email_username_magic"><?php echo esc_html(apply_filters('chrmrtns_kla_change_form_label', $login_label)); ?></label><br>
                        <input type="text" name="user_email_username" id="user_email_username_magic" class="input" value="" size="20" required aria-required="true" />
                    </p>
                    <?php wp_nonce_field('chrmrtns_kla_keyless_login_request', 'nonce', false); ?>
                    <input type="hidden" name="chrmrtns_kla_magic_form" value="1" />
                    <?php if (!empty($atts['redirect'])): ?>
                        <input type="hidden" name="chrmrtns_kla_redirect" value="<?php echo esc_url($atts['redirect']); ?>" />
                    <?php endif; ?>
                    <p class="submit">
                        <input type="submit" name="chrmrtns_kla_submit" id="chrmrtns_kla_submit" class="button-primary" value="<?php esc_html_e('Send me the link', 'keyless-auth'); ?>" />
                    </p>
                </form>
            </div>
        </div>

        <style>
        .chrmrtns-full-login-container {
            max-width: 500px;
            margin: 0 auto;
        }

        .chrmrtns-standard-login,
        .chrmrtns-magic-login {
            background: #f9f9f9;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .chrmrtns-standard-login h4,
        .chrmrtns-magic-login h4 {
            margin-top: 0;
            color: #333;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }

        .chrmrtns-login-separator {
            text-align: center;
            margin: 15px 0;
            position: relative;
        }

        .chrmrtns-login-separator:before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #ddd;
        }

        .chrmrtns-login-separator span {
            background: #fff;
            padding: 0 15px;
            color: #666;
            font-weight: bold;
            position: relative;
        }

        .chrmrtns-magic-description {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 15px;
        }

        .chrmrtns-forgot-password {
            margin-top: 10px;
            text-align: center;
        }

        .chrmrtns-login-title {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        </style>
        </div>
        <?php
    }
}
