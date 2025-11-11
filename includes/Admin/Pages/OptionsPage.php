<?php
/**
 * Options page for Keyless Auth admin
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Admin\Pages;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class OptionsPage {

    /**
     * Render the options page
     */
    public function render() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }

        // Handle form submission
        if (isset($_POST['submit_options']) && isset($_POST['chrmrtns_kla_options_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_options_nonce'])), 'chrmrtns_kla_options_action')) {
            $enable_wp_login = isset($_POST['chrmrtns_kla_enable_wp_login']) ? '1' : '0';
            update_option('chrmrtns_kla_enable_wp_login', $enable_wp_login);

            $enable_woocommerce = isset($_POST['chrmrtns_kla_enable_woocommerce']) ? '1' : '0';
            update_option('chrmrtns_kla_enable_woocommerce', $enable_woocommerce);

            $enable_rest_api = isset($_POST['chrmrtns_kla_enable_rest_api']) ? '1' : '0';
            update_option('chrmrtns_kla_enable_rest_api', $enable_rest_api);

            $custom_login_url = isset($_POST['chrmrtns_kla_custom_login_url']) ? esc_url_raw(wp_unslash($_POST['chrmrtns_kla_custom_login_url'])) : '';
            update_option('chrmrtns_kla_custom_login_url', $custom_login_url);

            $redirect_wp_login = isset($_POST['chrmrtns_kla_redirect_wp_login']) ? '1' : '0';
            update_option('chrmrtns_kla_redirect_wp_login', $redirect_wp_login);

            $custom_redirect_url = isset($_POST['chrmrtns_kla_custom_redirect_url']) ? esc_url_raw(wp_unslash($_POST['chrmrtns_kla_custom_redirect_url'])) : '';
            update_option('chrmrtns_kla_custom_redirect_url', $custom_redirect_url);

            $custom_2fa_setup_url = isset($_POST['chrmrtns_kla_custom_2fa_setup_url']) ? esc_url_raw(wp_unslash($_POST['chrmrtns_kla_custom_2fa_setup_url'])) : '';
            update_option('chrmrtns_kla_custom_2fa_setup_url', $custom_2fa_setup_url);

            $dark_mode_setting = isset($_POST['chrmrtns_kla_dark_mode_setting']) ? sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_dark_mode_setting'])) : 'auto';
            update_option('chrmrtns_kla_dark_mode_setting', $this->sanitize_dark_mode_setting($dark_mode_setting));

            $disable_xmlrpc = isset($_POST['chrmrtns_kla_disable_xmlrpc']) ? '1' : '0';
            update_option('chrmrtns_kla_disable_xmlrpc', $disable_xmlrpc);

            $disable_app_passwords = isset($_POST['chrmrtns_kla_disable_app_passwords']) ? '1' : '0';
            update_option('chrmrtns_kla_disable_app_passwords', $disable_app_passwords);

            $prevent_user_enumeration = isset($_POST['chrmrtns_kla_prevent_user_enumeration']) ? '1' : '0';
            update_option('chrmrtns_kla_prevent_user_enumeration', $prevent_user_enumeration);

            $custom_password_reset = isset($_POST['chrmrtns_kla_custom_password_reset']) ? '1' : '0';
            update_option('chrmrtns_kla_custom_password_reset', $custom_password_reset);

            $custom_password_reset_url = isset($_POST['chrmrtns_kla_custom_password_reset_url']) ? esc_url_raw(wp_unslash($_POST['chrmrtns_kla_custom_password_reset_url'])) : '';
            update_option('chrmrtns_kla_custom_password_reset_url', $custom_password_reset_url);

            $support_url = isset($_POST['chrmrtns_kla_support_url']) ? esc_url_raw(wp_unslash($_POST['chrmrtns_kla_support_url'])) : '';
            update_option('chrmrtns_kla_support_url', $support_url);

            // Handle 2FA settings
            $enable_2fa = isset($_POST['chrmrtns_kla_2fa_enabled']) ? true : false;
            update_option('chrmrtns_kla_2fa_enabled', $enable_2fa);

            $required_roles = isset($_POST['chrmrtns_kla_2fa_required_roles']) ? array_map('sanitize_text_field', wp_unslash($_POST['chrmrtns_kla_2fa_required_roles'])) : array();
            update_option('chrmrtns_kla_2fa_required_roles', $required_roles);

            $grace_period = isset($_POST['chrmrtns_kla_2fa_grace_period']) ? intval($_POST['chrmrtns_kla_2fa_grace_period']) : 10;
            update_option('chrmrtns_kla_2fa_grace_period', $grace_period);

            $grace_message = isset($_POST['chrmrtns_kla_2fa_grace_message']) ? sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_2fa_grace_message'])) : __('Your account requires 2FA setup within {days} days for security.', 'keyless-auth');
            update_option('chrmrtns_kla_2fa_grace_message', $grace_message);

            $max_attempts = isset($_POST['chrmrtns_kla_2fa_max_attempts']) ? intval($_POST['chrmrtns_kla_2fa_max_attempts']) : 5;
            update_option('chrmrtns_kla_2fa_max_attempts', $max_attempts);

            // Handle emergency mode setting - only show message if it actually changed
            $old_emergency_mode = get_option('chrmrtns_kla_2fa_emergency_disable', false);
            $emergency_mode = isset($_POST['chrmrtns_kla_2fa_emergency_disable']) ? true : false;

            // Convert to boolean for consistent comparison (in case stored as string)
            $old_emergency_mode = (bool) $old_emergency_mode;

            update_option('chrmrtns_kla_2fa_emergency_disable', $emergency_mode);

            // Only show message if emergency mode setting actually changed (strict comparison)
            if ($old_emergency_mode !== $emergency_mode) {
                if ($emergency_mode) {
                    echo '<div class="notice notice-warning"><p>' . esc_html__('Emergency mode is now enabled. 2FA system is disabled for all users.', 'keyless-auth') . '</p></div>';
                } else {
                    echo '<div class="notice notice-success"><p>' . esc_html__('Emergency mode is disabled. 2FA system is now active.', 'keyless-auth') . '</p></div>';
                }
            }

            $lockout_duration = isset($_POST['chrmrtns_kla_2fa_lockout_duration']) ? intval($_POST['chrmrtns_kla_2fa_lockout_duration']) : 15;
            update_option('chrmrtns_kla_2fa_lockout_duration', $lockout_duration);

            add_settings_error('chrmrtns_kla_options', 'settings_updated', __('Options saved successfully!', 'keyless-auth'), 'updated');
        }

        settings_errors('chrmrtns_kla_options');

        $enable_wp_login = get_option('chrmrtns_kla_enable_wp_login', '0');
        $enable_woocommerce = get_option('chrmrtns_kla_enable_woocommerce', '0');
        $enable_rest_api = get_option('chrmrtns_kla_enable_rest_api', '0');
        $custom_login_url = get_option('chrmrtns_kla_custom_login_url', '');
        $custom_redirect_url = get_option('chrmrtns_kla_custom_redirect_url', '');
        $custom_2fa_setup_url = get_option('chrmrtns_kla_custom_2fa_setup_url', '');

        // Get 2FA settings
        $enable_2fa = get_option('chrmrtns_kla_2fa_enabled', false);
        $emergency_disable = get_option('chrmrtns_kla_2fa_emergency_disable', false);
        $required_roles = get_option('chrmrtns_kla_2fa_required_roles', array());
        $grace_period = get_option('chrmrtns_kla_2fa_grace_period', 10);
        $grace_message = get_option('chrmrtns_kla_2fa_grace_message', __('Your account requires 2FA setup within {days} days for security.', 'keyless-auth'));
        $max_attempts = get_option('chrmrtns_kla_2fa_max_attempts', 5);
        $lockout_duration = get_option('chrmrtns_kla_2fa_lockout_duration', 15);

        // Get available roles
        $wp_roles = wp_roles();
        $available_roles = $wp_roles->get_names();
        ?>
        <div class="wrap">
            <h1 class="chrmrtns-header">
                <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
                <?php esc_html_e('Keyless Auth - Options', 'keyless-auth'); ?>
            </h1>
            <h2><?php esc_html_e('Plugin Options', 'keyless-auth'); ?></h2>

            <form method="post" action="">
                <?php wp_nonce_field('chrmrtns_kla_options_action', 'chrmrtns_kla_options_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_enable_wp_login"><?php esc_html_e('Enable Login on wp-login.php', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="chrmrtns_kla_enable_wp_login" name="chrmrtns_kla_enable_wp_login" value="1" <?php checked($enable_wp_login, '1'); ?> />
                            <p class="description">
                                <?php esc_html_e('Add a magic login field to the WordPress login page (wp-login.php). Note: This option is incompatible with the wp-login.php redirect option below.', 'keyless-auth'); ?>
                            </p>
                            <?php
                            $redirect_wp_login = get_option('chrmrtns_kla_redirect_wp_login', '0') === '1';
                            if ($enable_wp_login === '1' && $redirect_wp_login): ?>
                                <div class="notice notice-warning inline" style="margin: 10px 0 0 0; padding: 10px;">
                                    <p><strong><?php esc_html_e('Notice:', 'keyless-auth'); ?></strong>
                                    <?php esc_html_e('This option is currently inactive because "Redirect all wp-login.php requests" is enabled below. The redirect takes priority and prevents the magic login field from appearing on wp-login.php.', 'keyless-auth'); ?></p>
                                    <p style="margin-bottom: 0;"><?php esc_html_e('To use magic login on wp-login.php, disable the redirect option below.', 'keyless-auth'); ?></p>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php if (class_exists('WooCommerce')) : ?>
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_enable_woocommerce"><?php esc_html_e('Enable WooCommerce Integration', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="chrmrtns_kla_enable_woocommerce" name="chrmrtns_kla_enable_woocommerce" value="1" <?php checked($enable_woocommerce, '1'); ?> />
                            <p class="description">
                                <?php esc_html_e('Add magic link authentication to WooCommerce login forms (My Account and Checkout pages). A collapsible "Or login with magic link instead" option will appear below the password field.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_enable_rest_api"><?php esc_html_e('Enable REST API (Beta)', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="chrmrtns_kla_enable_rest_api" name="chrmrtns_kla_enable_rest_api" value="1" <?php checked($enable_rest_api, '1'); ?> />
                            <p class="description">
                                <?php esc_html_e('Enable REST API endpoints for magic link authentication. This runs in parallel with existing AJAX handlers for backward compatibility. Endpoint: /wp-json/keyless-auth/v1/request-login', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_custom_login_url"><?php esc_html_e('Custom Login Page URL', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="chrmrtns_kla_custom_login_url" name="chrmrtns_kla_custom_login_url" value="<?php echo esc_attr($custom_login_url); ?>" class="regular-text" placeholder="<?php echo esc_attr(wp_login_url()); ?>" />
                            <p class="description">
                                <?php esc_html_e('Optional: Specify a custom login page URL. When users need to login (like in 2FA flow), they\'ll be redirected here instead of wp-login.php. Leave empty to use the default WordPress login page.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_redirect_wp_login"><?php esc_html_e('Redirect wp-login.php', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="chrmrtns_kla_redirect_wp_login" name="chrmrtns_kla_redirect_wp_login" value="1" <?php checked(get_option('chrmrtns_kla_redirect_wp_login', '0'), '1'); ?> />
                            <label for="chrmrtns_kla_redirect_wp_login"><?php esc_html_e('Redirect all wp-login.php requests to custom login page', 'keyless-auth'); ?></label>
                            <p class="description">
                                <?php esc_html_e('When enabled, all requests to wp-login.php will be redirected to your custom login page. Note: When enabled, this automatically disables magic login integration on wp-login.php since users will be redirected away. Emergency bypass: add ?kla_use_wp_login=1 to access wp-login.php directly.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_custom_redirect_url"><?php esc_html_e('Post-Login Redirect URL', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="chrmrtns_kla_custom_redirect_url" name="chrmrtns_kla_custom_redirect_url" value="<?php echo esc_attr($custom_redirect_url); ?>" class="regular-text" placeholder="<?php echo esc_attr(admin_url()); ?>" />
                            <p class="description">
                                <?php esc_html_e('Optional: Specify where users should be redirected after successful login via magic link or 2FA. This applies to all users regardless of role. Leave empty to use default WordPress behavior (admin dashboard for admins, homepage for others).', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_custom_2fa_setup_url"><?php esc_html_e('2FA Setup Page URL', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <input type="url" id="chrmrtns_kla_custom_2fa_setup_url" name="chrmrtns_kla_custom_2fa_setup_url" value="<?php echo esc_attr($custom_2fa_setup_url); ?>" class="regular-text" placeholder="<?php echo esc_attr(home_url('/2fa/')); ?>" />
                            <p class="description">
                                <?php esc_html_e('Optional: Specify a custom page where users can set up 2FA using the [keyless-auth-2fa] shortcode. When users need to configure 2FA, email notifications will link here instead of wp-login.php. Leave empty to use the default WordPress login page.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <hr style="margin: 40px 0; border: 0; border-top: 1px solid #dcdcde;">

                <!-- Dark Mode Settings Section -->
                <h2><?php esc_html_e('Appearance & Theme Settings', 'keyless-auth'); ?></h2>
                <p class="description" style="margin-bottom: 20px;">
                    <?php esc_html_e('Control how login forms appear in light and dark mode themes.', 'keyless-auth'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_dark_mode_setting"><?php esc_html_e('Dark Mode Behavior', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <?php $dark_mode_setting = get_option('chrmrtns_kla_dark_mode_setting', 'auto'); ?>
                            <select id="chrmrtns_kla_dark_mode_setting" name="chrmrtns_kla_dark_mode_setting">
                                <option value="auto" <?php selected($dark_mode_setting, 'auto'); ?>><?php esc_html_e('Auto (System Preference + Theme Classes)', 'keyless-auth'); ?></option>
                                <option value="light" <?php selected($dark_mode_setting, 'light'); ?>><?php esc_html_e('Light Only (No Dark Mode)', 'keyless-auth'); ?></option>
                                <option value="dark" <?php selected($dark_mode_setting, 'dark'); ?>><?php esc_html_e('Dark Only (Force Dark Mode)', 'keyless-auth'); ?></option>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Control how login forms appear in dark mode. Auto detects system preferences and theme dark mode classes. Light Only forces light theme. Dark Only forces dark theme.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <hr style="margin: 40px 0; border: 0; border-top: 1px solid #dcdcde;">

                <!-- Security Settings Section -->
                <h2><?php esc_html_e('Security Settings', 'keyless-auth'); ?></h2>
                <p class="description" style="margin-bottom: 20px;">
                    <?php esc_html_e('Additional security options to harden your WordPress installation.', 'keyless-auth'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_disable_xmlrpc"><?php esc_html_e('Disable XML-RPC', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <?php $disable_xmlrpc = get_option('chrmrtns_kla_disable_xmlrpc', '0'); ?>
                            <input type="checkbox" id="chrmrtns_kla_disable_xmlrpc" name="chrmrtns_kla_disable_xmlrpc" value="1" <?php checked($disable_xmlrpc, '1'); ?> />
                            <p class="description">
                                <?php esc_html_e('Disable WordPress XML-RPC interface to prevent brute force attacks and reduce attack surface. Only disable if you don\'t use XML-RPC features (Jetpack, mobile apps, pingbacks).', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_disable_app_passwords"><?php esc_html_e('Disable Application Passwords', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <?php $disable_app_passwords = get_option('chrmrtns_kla_disable_app_passwords', '0'); ?>
                            <input type="checkbox" id="chrmrtns_kla_disable_app_passwords" name="chrmrtns_kla_disable_app_passwords" value="1" <?php checked($disable_app_passwords, '1'); ?> />
                            <p class="description">
                                <?php esc_html_e('Disable WordPress Application Passwords to prevent REST API and XML-RPC authentication. Only disable if you don\'t use programmatic access (mobile apps, CI/CD tools, third-party integrations).', 'keyless-auth'); ?>
                                <br><strong style="color: #d63638;"><?php esc_html_e('Warning:', 'keyless-auth'); ?></strong> <?php esc_html_e('Disabling this will break REST API and XML-RPC authentication. Users will not be able to authenticate via Application Passwords.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_prevent_user_enumeration"><?php esc_html_e('Prevent User Enumeration', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <?php $prevent_user_enumeration = get_option('chrmrtns_kla_prevent_user_enumeration', '0'); ?>
                            <input type="checkbox" id="chrmrtns_kla_prevent_user_enumeration" name="chrmrtns_kla_prevent_user_enumeration" value="1" <?php checked($prevent_user_enumeration, '1'); ?> />
                            <p class="description">
                                <?php esc_html_e('Prevent attackers from discovering usernames via REST API, author archives, login errors, and comment author classes. Blocks common user enumeration techniques used to gather usernames for brute force attacks.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_custom_password_reset"><?php esc_html_e('Custom Password Reset Page', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <?php $custom_password_reset = get_option('chrmrtns_kla_custom_password_reset', '0'); ?>
                            <input type="checkbox" id="chrmrtns_kla_custom_password_reset" name="chrmrtns_kla_custom_password_reset" value="1" <?php checked($custom_password_reset, '1'); ?> />
                            <p class="description">
                                <?php esc_html_e('Enable custom password reset page. Create a page with the [keyless_password_reset] shortcode and specify the URL below.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_custom_password_reset_url"><?php esc_html_e('Password Reset Page URL', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <?php $custom_password_reset_url = get_option('chrmrtns_kla_custom_password_reset_url', ''); ?>
                            <input type="url" id="chrmrtns_kla_custom_password_reset_url" name="chrmrtns_kla_custom_password_reset_url" value="<?php echo esc_attr($custom_password_reset_url); ?>" class="regular-text" placeholder="<?php esc_attr_e('https://yoursite.com/reset-password', 'keyless-auth'); ?>" />
                            <p class="description">
                                <?php esc_html_e('Full URL to your password reset page. The "Forgot password?" link will use this URL. Leave empty to use default wp-login.php.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_support_url"><?php esc_html_e('Support URL (Optional)', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <?php $support_url = get_option('chrmrtns_kla_support_url', ''); ?>
                            <input type="url" id="chrmrtns_kla_support_url" name="chrmrtns_kla_support_url" value="<?php echo esc_attr($support_url); ?>" class="regular-text" placeholder="<?php esc_attr_e('https://yoursite.com/support', 'keyless-auth'); ?>" />
                            <p class="description">
                                <?php esc_html_e('Enter a support page URL to display a help footer on the password reset page. Leave empty to hide the support footer.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <hr style="margin: 40px 0; border: 0; border-top: 1px solid #dcdcde;">

                <!-- 2FA Settings Section -->
                <h2><?php esc_html_e('Two-Factor Authentication (2FA)', 'keyless-auth'); ?></h2>
                <p class="description" style="margin-bottom: 20px;">
                    <?php esc_html_e('Add an extra layer of security with TOTP-based two-factor authentication using authenticator apps.', 'keyless-auth'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_2fa_enabled"><?php esc_html_e('Enable 2FA System', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <input type="checkbox" id="chrmrtns_kla_2fa_enabled" name="chrmrtns_kla_2fa_enabled" value="1" <?php checked($enable_2fa, true); ?> />
                            <p class="description">
                                <?php esc_html_e('Enable TOTP authenticator app support for all WordPress logins. Only enable if you don\'t have other 2FA solutions active.', 'keyless-auth'); ?>
                                <br><strong><?php esc_html_e('API Access:', 'keyless-auth'); ?></strong> <?php esc_html_e('REST API and XML-RPC automatically bypass 2FA when using Application Passwords.', 'keyless-auth'); ?>
                                <br><strong><?php esc_html_e('User Setup:', 'keyless-auth'); ?></strong> <?php esc_html_e('Users can access 2FA setup using the [keyless-auth-2fa] shortcode on any page.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <label for="chrmrtns_kla_2fa_emergency_disable"><?php esc_html_e('Emergency Disable 2FA', 'keyless-auth'); ?></label>
                        </th>
                        <td>
                            <?php if (defined('CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY') && CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY === true) { ?>
                                <div style="padding: 15px; background: #fef7f7; border-radius: 5px; border-left: 4px solid #dc3232; margin-bottom: 10px;">
                                    <p style="margin: 0; color: #dc3232; font-weight: bold;">
                                        🚨 <?php esc_html_e('Emergency mode is enabled via wp-config.php constant.', 'keyless-auth'); ?>
                                    </p>
                                    <p style="margin: 10px 0 0 0; color: #666;">
                                        <?php esc_html_e('Remove the CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY constant from wp-config.php to manage emergency mode here.', 'keyless-auth'); ?>
                                    </p>
                                </div>
                                <input type="checkbox" id="chrmrtns_kla_2fa_emergency_disable" name="chrmrtns_kla_2fa_emergency_disable" value="1" disabled />
                                <span style="color: #666;"><?php esc_html_e('Controlled by wp-config.php', 'keyless-auth'); ?></span>
                            <?php } else { ?>
                                <input type="checkbox" id="chrmrtns_kla_2fa_emergency_disable" name="chrmrtns_kla_2fa_emergency_disable" value="1" <?php checked($emergency_disable, true); ?> />
                                <?php if ($emergency_disable) { ?>
                                    <span style="color: #dc3232; font-weight: bold;"><?php esc_html_e('2FA system is currently disabled', 'keyless-auth'); ?></span>
                                <?php } else { ?>
                                    <span><?php esc_html_e('Disable 2FA system temporarily', 'keyless-auth'); ?></span>
                                <?php } ?>
                            <?php } ?>
                            <p class="description">
                                <?php esc_html_e('Temporarily disable all 2FA requirements for troubleshooting or emergency access. Users can login normally without 2FA when enabled.', 'keyless-auth'); ?>
                                <br><strong><?php esc_html_e('Warning:', 'keyless-auth'); ?></strong> <?php esc_html_e('This reduces security. Only use when necessary.', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- 2FA Role Requirements -->
                <div id="chrmrtns-2fa-settings" style="<?php echo $enable_2fa ? '' : 'display: none;'; ?>">
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Required for User Roles', 'keyless-auth'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><span><?php esc_html_e('Required User Roles', 'keyless-auth'); ?></span></legend>
                                    <?php foreach ($available_roles as $role_key => $role_name): ?>
                                        <label>
                                            <input type="checkbox" name="chrmrtns_kla_2fa_required_roles[]" value="<?php echo esc_attr($role_key); ?>" <?php checked(in_array($role_key, $required_roles, true)); ?> />
                                            <?php echo esc_html($role_name); ?>
                                        </label><br>
                                    <?php endforeach; ?>
                                    <p class="description">
                                        <?php esc_html_e('Users with these roles MUST set up 2FA. Other users can optionally enable 2FA for enhanced security.', 'keyless-auth'); ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="chrmrtns_kla_2fa_grace_period"><?php esc_html_e('Grace Period', 'keyless-auth'); ?></label>
                            </th>
                            <td>
                                <select id="chrmrtns_kla_2fa_grace_period" name="chrmrtns_kla_2fa_grace_period">
                                    <?php for ($i = 1; $i <= 30; $i++): ?>
                                        <option value="<?php echo esc_attr($i); ?>" <?php selected($grace_period, $i); ?>><?php
                                        /* translators: %d: number of days for grace period */
                                        echo esc_html(sprintf(_n('%d day', '%d days', $i, 'keyless-auth'), $i)); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('How many days users have to set up 2FA after role requirement is enabled.', 'keyless-auth'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="chrmrtns_kla_2fa_grace_message"><?php esc_html_e('Grace Period Message', 'keyless-auth'); ?></label>
                            </th>
                            <td>
                                <input type="text" id="chrmrtns_kla_2fa_grace_message" name="chrmrtns_kla_2fa_grace_message" value="<?php echo esc_attr($grace_message); ?>" class="regular-text" />
                                <p class="description">
                                    <?php esc_html_e('Message shown to users during grace period. Use {days} placeholder for remaining days.', 'keyless-auth'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <!-- 2FA Security Settings -->
                    <h3><?php esc_html_e('Security Settings', 'keyless-auth'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="chrmrtns_kla_2fa_max_attempts"><?php esc_html_e('Max Failed Attempts', 'keyless-auth'); ?></label>
                            </th>
                            <td>
                                <select id="chrmrtns_kla_2fa_max_attempts" name="chrmrtns_kla_2fa_max_attempts">
                                    <?php for ($i = 3; $i <= 10; $i++): ?>
                                        <option value="<?php echo esc_attr($i); ?>" <?php selected($max_attempts, $i); ?>><?php echo esc_html($i); ?></option>
                                    <?php endfor; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Number of failed 2FA attempts before user is temporarily locked out.', 'keyless-auth'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="chrmrtns_kla_2fa_lockout_duration"><?php esc_html_e('Lockout Duration', 'keyless-auth'); ?></label>
                            </th>
                            <td>
                                <select id="chrmrtns_kla_2fa_lockout_duration" name="chrmrtns_kla_2fa_lockout_duration">
                                    <?php
                                    $durations = array(5 => '5 minutes', 10 => '10 minutes', 15 => '15 minutes', 30 => '30 minutes', 60 => '1 hour');
                                    foreach ($durations as $value => $label): ?>
                                        <option value="<?php echo esc_attr($value); ?>" <?php selected($lockout_duration, $value); ?>><?php echo esc_html($label); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('How long to lock users out after too many failed 2FA attempts.', 'keyless-auth'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <!-- 2FA User Management -->
                    <h3><?php esc_html_e('User Management', 'keyless-auth'); ?></h3>
                    <p class="description"><?php esc_html_e('2FA user management has been moved to a dedicated page for better usability.', 'keyless-auth'); ?></p>
                    <p><a href="<?php echo esc_url(admin_url('admin.php?page=keyless-auth-2fa-users')); ?>" class="button"><?php esc_html_e('Manage 2FA Users', 'keyless-auth'); ?></a></p>
                </div>

                <?php submit_button(__('Save Options', 'keyless-auth'), 'primary', 'submit_options'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Sanitize dark mode setting
     *
     * @param string $input Input value
     * @return string Sanitized value
     */
    private function sanitize_dark_mode_setting($input) {
        $valid_options = array('auto', 'light', 'dark');
        return in_array($input, $valid_options, true) ? $input : 'auto';
    }

    /**
     * Get the post-login redirect URL (custom or default)
     *
     * @param int $user_id User ID for context
     * @return string Redirect URL
     */
    public static function get_redirect_url($user_id = 0) {
        $custom_redirect_url = get_option('chrmrtns_kla_custom_redirect_url', '');
        if (!empty($custom_redirect_url)) {
            return esc_url($custom_redirect_url);
        }

        // Default WordPress behavior
        if ($user_id > 0) {
            $user = get_user_by('ID', $user_id);
            if ($user && (user_can($user, 'manage_options') || user_can($user, 'edit_others_posts'))) {
                return admin_url();
            }
        }

        return home_url();
    }
}
