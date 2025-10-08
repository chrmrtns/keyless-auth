<?php
/**
 * Core authentication functionality for Keyless Auth
 * 
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_KLA_Core {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_nopriv_chrmrtns_kla_request_login_code', array($this, 'handle_login_request'));
        add_action('wp_ajax_chrmrtns_kla_request_login_code', array($this, 'handle_login_request'));
        add_action('wp_loaded', array($this, 'handle_login_link'), 1);
        add_action('init', array($this, 'handle_form_submission'));
        add_shortcode('keyless-auth', array($this, 'render_login_form'));
        add_shortcode('keyless-auth-full', array($this, 'render_full_login_form'));

        // wp-login.php integration - only add hooks if enabled
        if (get_option('chrmrtns_kla_enable_wp_login', '0') === '1') {
            add_action('login_footer', array($this, 'chrmrtns_kla_add_wp_login_field'));
            add_action('login_init', array($this, 'chrmrtns_kla_handle_wp_login_submission'));
            add_action('login_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        }

        // Hook early to catch wp-login.php requests for redirect
        add_action('init', array($this, 'chrmrtns_kla_maybe_redirect_wp_login'), 1);

        // Disable XML-RPC if option is enabled
        if (get_option('chrmrtns_kla_disable_xmlrpc', '0') === '1') {
            add_filter('xmlrpc_enabled', '__return_false');
        }

        // Disable Application Passwords if option is enabled
        if (get_option('chrmrtns_kla_disable_app_passwords', '0') === '1') {
            add_filter('wp_is_application_passwords_available', '__return_false');
        }

        // Prevent user enumeration if option is enabled
        if (get_option('chrmrtns_kla_prevent_user_enumeration', '0') === '1') {
            add_action('init', array($this, 'prevent_user_enumeration'));
        }
    }

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
     * Render login form shortcode
     */
    public function render_login_form($atts = array()) {
        // Enqueue styles when shortcode is rendered
        $this->enqueue_frontend_scripts();

        // Parse attributes with defaults
        $atts = shortcode_atts(array(
            'redirect' => '',
            'button_text' => '',
            'description' => '',
            'label' => ''
        ), $atts, 'keyless-auth');
        ob_start();
        
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Form display logic, not processing
        $account = (isset($_POST['user_email_username'])) ? sanitize_text_field(wp_unslash($_POST['user_email_username'])) : false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
        $error_token = (isset($_GET['chrmrtns_kla_error_token'])) ? sanitize_key(wp_unslash($_GET['chrmrtns_kla_error_token'])) : false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display  
        $adminapp_error = (isset($_GET['chrmrtns_kla_adminapp_error'])) ? sanitize_key(wp_unslash($_GET['chrmrtns_kla_adminapp_error'])) : false;

        $sent_link = get_option('chrmrtns_kla_login_request_error');

        if ($account && !is_wp_error($sent_link)) {
            echo '<p class="chrmrtns-box chrmrtns-success">' . wp_kses_post(apply_filters('chrmrtns_kla_success_link_msg', esc_html__('Please check your email. You will soon receive an email with a login link.', 'keyless-auth'))) . '</p>';
        } elseif (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            echo '<p class="chrmrtns-box chrmrtns-alert">' . wp_kses_post(apply_filters('chrmrtns_kla_success_login_msg', sprintf(
                /* translators: %1$s: user display name with link, %2$s: logout link */
                esc_html__('You are currently logged in as %1$s. %2$s', 'keyless-auth'), 
                '<a href="' . esc_url(get_author_posts_url($current_user->ID)) . '" title="' . esc_attr($current_user->display_name) . '">' . esc_html($current_user->display_name) . '</a>', 
                '<a href="' . esc_url(wp_logout_url($this->get_current_page_url())) . '" title="' . esc_html__('Log out of this account', 'keyless-auth') . '">' . esc_html__('Log out', 'keyless-auth') . ' &raquo;</a>'
            ))) . '</p><!-- .alert-->';
        } else {
            if (is_wp_error($sent_link)) {
                echo '<p class="chrmrtns-box chrmrtns-error">' . esc_html(apply_filters('chrmrtns_error', $sent_link->get_error_message())) . '</p>';
            }
            if ($error_token) {
                echo '<p class="chrmrtns-box chrmrtns-error">' . wp_kses_post(apply_filters('chrmrtns_kla_invalid_token_error', __('Your token has probably expired. Please try again.', 'keyless-auth'))) . '</p>';
            }
            if ($adminapp_error) { // admin approval compatibility
                echo '<p class="chrmrtns-box chrmrtns-error">' . wp_kses_post(apply_filters('chrmrtns_kla_admin_approval_error', __('Your account needs to be approved by an admin before you can log-in.', 'keyless-auth'))) . '</p>';
            }
            
            // Render the login form
            $this->render_login_form_html($atts);
        }

        return ob_get_clean();
    }

    /**
     * Render login form HTML
     */
    private function render_login_form_html($atts = array()) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        
        // Setting up the label for the password request form based on the Allows Users to Login With Profile Builder Option
        $login_label = __('Login with email or username', 'keyless-auth');
        
        if (is_plugin_active('profile-builder-pro/index.php') || is_plugin_active('profile-builder/index.php') || is_plugin_active('profile-builder-hobbyist/index.php')) {
            $wppb_general_options = get_option('wppb_general_settings');
            if (isset($wppb_general_options['loginWith']) && ($wppb_general_options['loginWith'] == 'email')) {
                $login_label = __('Login with email', 'keyless-auth');
            } elseif (isset($wppb_general_options['loginWith']) && ($wppb_general_options['loginWith'] == 'username')) {
                $login_label = __('Login with username', 'keyless-auth');
            }
        }

        // Determine label text
        $label_text = !empty($atts['label']) ? $atts['label'] : $login_label;

        // Determine button text
        $button_text = !empty($atts['button_text']) ? $atts['button_text'] : __('Send me the link', 'keyless-auth');

        ?>
        <div class="chrmrtns-kla-form-wrapper">
            <?php if (!empty($atts['description'])): ?>
                <p class="chrmrtns-kla-description"><?php echo wp_kses_post($atts['description']); ?></p>
            <?php endif; ?>
            <form method="post" class="chrmrtns-form">
                <p>
                    <label for="user_email_username"><?php echo esc_html(apply_filters('chrmrtns_kla_change_form_label', $label_text)); ?></label><br>
                    <input type="text" name="user_email_username" id="user_email_username" class="input" value="" size="20" required />
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
     * Render full login form with both standard and magic link options
     */
    public function render_full_login_form($atts = array()) {
        // Enqueue styles when shortcode is rendered
        $this->enqueue_frontend_scripts();

        // Parse attributes with defaults
        $atts = shortcode_atts(array(
            'redirect' => '',
            'show_title' => 'yes',
            'title_text' => __('Login', 'keyless-auth')
        ), $atts, 'keyless-auth-full');

        ob_start();

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Form display logic, not processing
        $account = (isset($_POST['user_email_username'])) ? sanitize_text_field(wp_unslash($_POST['user_email_username'])) : false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
        $error_token = (isset($_GET['chrmrtns_kla_error_token'])) ? sanitize_key(wp_unslash($_GET['chrmrtns_kla_error_token'])) : false;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- URL parameters for error display
        $adminapp_error = (isset($_GET['chrmrtns_kla_adminapp_error'])) ? sanitize_key(wp_unslash($_GET['chrmrtns_kla_adminapp_error'])) : false;

        $sent_link = get_option('chrmrtns_kla_login_request_error');

        // Show success message for magic link
        if ($account && !is_wp_error($sent_link)) {
            echo '<p class="chrmrtns-box chrmrtns-success">' . wp_kses_post(apply_filters('chrmrtns_kla_success_link_msg', esc_html__('Please check your email. You will soon receive an email with a login link.', 'keyless-auth'))) . '</p>';
        } elseif (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            echo '<p class="chrmrtns-box chrmrtns-alert">' . wp_kses_post(apply_filters('chrmrtns_kla_success_login_msg', sprintf(
                /* translators: %1$s: user display name with link, %2$s: logout link */
                esc_html__('You are currently logged in as %1$s. %2$s', 'keyless-auth'),
                '<a href="' . esc_url(get_author_posts_url($current_user->ID)) . '" title="' . esc_attr($current_user->display_name) . '">' . esc_html($current_user->display_name) . '</a>',
                '<a href="' . esc_url(wp_logout_url($this->get_current_page_url())) . '" title="' . esc_html__('Log out of this account', 'keyless-auth') . '">' . esc_html__('Log out', 'keyless-auth') . ' &raquo;</a>'
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

            // Show title if enabled
            if ($atts['show_title'] === 'yes') {
                echo '<h3 class="chrmrtns-login-title">' . esc_html($atts['title_text']) . '</h3>';
            }

            $this->render_full_login_form_html($atts);
        }

        return ob_get_clean();
    }

    /**
     * Render full login form HTML with both standard and magic link options
     */
    private function render_full_login_form_html($atts = array()) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');

        // Setting up the label for the password request form based on the Profile Builder Option
        $login_label = __('Login with email or username', 'keyless-auth');

        if (is_plugin_active('profile-builder-pro/index.php') || is_plugin_active('profile-builder/index.php') || is_plugin_active('profile-builder-hobbyist/index.php')) {
            $wppb_general_options = get_option('wppb_general_settings');
            if (isset($wppb_general_options['loginWith']) && ($wppb_general_options['loginWith'] == 'email')) {
                $login_label = __('Login with email', 'keyless-auth');
            } elseif (isset($wppb_general_options['loginWith']) && ($wppb_general_options['loginWith'] == 'username')) {
                $login_label = __('Login with username', 'keyless-auth');
            }
        }

        $redirect_to = !empty($atts['redirect']) ? esc_url($atts['redirect']) : $this->get_current_page_url();
        ?>
        <div class="chrmrtns-kla-form-wrapper">
        <div class="chrmrtns-full-login-container">
            <!-- Standard WordPress Login Form -->
            <div class="chrmrtns-standard-login" data-form-type="password">
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
                    <a href="<?php echo esc_url(wp_lostpassword_url($redirect_to)); ?>"><?php esc_html_e('Forgot your password?', 'keyless-auth'); ?></a>
                </p>
            </div>

            <div class="chrmrtns-login-separator">
                <span><?php esc_html_e('OR', 'keyless-auth'); ?></span>
            </div>

            <!-- Magic Link Login Form -->
            <div class="chrmrtns-magic-login" data-form-type="magic">
                <h4><?php esc_html_e('Magic Link Login', 'keyless-auth'); ?></h4>
                <p class="chrmrtns-magic-description"><?php esc_html_e('No password required - we\'ll send you a secure login link via email.', 'keyless-auth'); ?></p>
                <form method="post" class="chrmrtns-form">
                    <p>
                        <label for="user_email_username_magic"><?php echo esc_html(apply_filters('chrmrtns_kla_change_form_label', $login_label)); ?></label><br>
                        <input type="text" name="user_email_username" id="user_email_username_magic" class="input" value="" size="20" required />
                    </p>
                    <?php wp_nonce_field('chrmrtns_kla_keyless_login_request', 'nonce', false); ?>
                    <input type="hidden" name="chrmrtns_kla_magic_form" value="1" />
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
        </div><!-- .chrmrtns-kla-form-wrapper -->
        <?php
    }

    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        // Only handle magic link form submissions, not standard WordPress login
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in handle_login_request()
        if (isset($_POST['chrmrtns_kla_magic_form']) && isset($_POST['user_email_username']) && isset($_POST['nonce'])) {
            $this->handle_login_request();
        }
    }
    
    /**
     * Handle login request
     */
    public function handle_login_request() {
        // Delete any existing error
        delete_option('chrmrtns_kla_login_request_error');
        
        if (!isset($_POST['user_email_username'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'chrmrtns_kla_keyless_login_request')) {
            $error = new WP_Error('nonce_failed', __('Security check failed. Please try again.', 'keyless-auth'));
            update_option('chrmrtns_kla_login_request_error', $error);
            return;
        }
        
        $user_email_username = sanitize_text_field(wp_unslash($_POST['user_email_username']));
        
        // Get user by email or username
        $user = $this->get_user_by_email_or_username($user_email_username);
        
        if (!$user) {
            $error = new WP_Error('invalid_user', __('The username or email you provided do not exist. Please try again.', 'keyless-auth'));
            update_option('chrmrtns_kla_login_request_error', $error);
            return;
        }
        
        // Check admin approval compatibility
        if ($this->is_admin_approval_required($user)) {
            wp_redirect(add_query_arg('chrmrtns_kla_adminapp_error', '1', $this->get_current_page_url()));
            exit;
        }
        
        // Generate and send login link
        if (!$this->send_login_email($user)) {
            $error = new WP_Error('email_failed', __('There was a problem sending your email. Please try again or contact an admin.', 'keyless-auth'));
            update_option('chrmrtns_kla_login_request_error', $error);
        }
    }
    
    /**
     * Get user by email or username
     */
    private function get_user_by_email_or_username($user_email_username) {
        if (is_email($user_email_username)) {
            return get_user_by('email', $user_email_username);
        } else {
            return get_user_by('login', $user_email_username);
        }
    }
    
    /**
     * Check if admin approval is required
     */
    private function is_admin_approval_required($user) {
        // Admin approval compatibility with Profile Builder
        if (function_exists('wppb_check_admin_approval')) {
            $admin_approval = get_user_meta($user->ID, 'wppb_approved', true);
            return ($admin_approval !== 'approved');
        }
        return false;
    }
    
    /**
     * Send login email
     */
    private function send_login_email($user) {
        $user_id = $user->ID;
        $user_email = $user->user_email;
        
        // Generate secure token
        $expiration_time = time() + apply_filters('chrmrtns_kla_change_link_expiration', 600); // 10 minutes default
        $token = $this->generate_secure_token($user_id, $expiration_time);

        // Store token in database
        if (class_exists('Chrmrtns_KLA_Database')) {
            $database = new Chrmrtns_KLA_Database();
            $database->store_login_token($user_id, $token, $expiration_time);
        } else {
            // Fallback to user meta (backwards compatibility)
            update_user_meta($user_id, 'chrmrtns_kla_login_token', $token);
            update_user_meta($user_id, 'chrmrtns_kla_login_token_expiration', $expiration_time);
        }
        
        // Create login URL with optional redirect
        $url_args = array(
            'chrmrtns_kla_token' => $token,
            'chrmrtns_kla_user_id' => $user_id
        );

        // Add custom redirect if provided via shortcode
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- POST data already validated in handle_login_request()
        if (isset($_POST['chrmrtns_kla_redirect']) && !empty($_POST['chrmrtns_kla_redirect'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Missing -- POST data already validated in handle_login_request()
            $url_args['chrmrtns_kla_redirect'] = esc_url_raw(wp_unslash($_POST['chrmrtns_kla_redirect']));
        }

        $login_url = add_query_arg($url_args, $this->get_current_page_url());
        
        // Get email template
        if (class_exists('Chrmrtns_KLA_Email_Templates')) {
            $email_templates = new Chrmrtns_KLA_Email_Templates();
            $email_body = $email_templates->get_email_template($user_email, $login_url);
        } else {
            // Fallback template
            $email_body = sprintf(
                /* translators: %1$s: site name, %2$s: login URL for href attribute, %3$s: login URL for display text */
                __('Hello! <br><br>Login at %1$s by visiting this url: <a href="%2$s" target="_blank">%3$s</a>', 'keyless-auth'),
                get_bloginfo('name'),
                esc_url($login_url),
                esc_url($login_url)
            );
        }
        
        $subject = sprintf(
            /* translators: %s: site name */
            __('Login at %s', 'keyless-auth'), 
            get_bloginfo('name')
        );
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($user_email, $subject, $email_body, apply_filters('chrmrtns_kla_email_headers', $headers));
    }
    
    /**
     * Generate secure token
     */
    private function generate_secure_token($user_id, $expiration_time) {
        return wp_hash($user_id . $expiration_time . wp_salt());
    }
    
    
    /**
     * Maybe redirect wp-login.php to custom login page
     */
    public function chrmrtns_kla_maybe_redirect_wp_login() {
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

        // Perform the redirect
        wp_redirect($custom_login_url);
        exit;
    }

    /**
     * Handle login link clicks
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
        if (!$this->validate_login_token($user_id, $token)) {
            wp_redirect(add_query_arg('chrmrtns_kla_error_token', '1', $this->get_current_page_url()));
            exit;
        }
        
        // Check if 2FA is required for this user
        $user = get_user_by('ID', $user_id);
        if ($user && class_exists('Chrmrtns_KLA_2FA_Core')) {
            $tfa_core = Chrmrtns_KLA_2FA_Core::get_instance();

            // Check if 2FA is enabled and required for this user
            if (get_option('chrmrtns_kla_2fa_enabled', false)) {
                global $chrmrtns_kla_database;
                $user_settings = $chrmrtns_kla_database ? $chrmrtns_kla_database->get_user_2fa_settings($user_id) : null;
                $role_required = $tfa_core->user_role_requires_2fa($user_id);

                // If user has 2FA enabled, redirect to 2FA verification
                // If role requires 2FA but user doesn't have it, check grace period first
                if ($user_settings && $user_settings->totp_enabled) {
                    // Check for custom redirect from shortcode first
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for redirect, validated with esc_url_raw()
                    $custom_redirect = isset($_GET['chrmrtns_kla_redirect']) ? esc_url_raw(wp_unslash($_GET['chrmrtns_kla_redirect'])) : '';

                    if (!empty($custom_redirect)) {
                        $redirect_url = $custom_redirect;
                    } else {
                        // Get redirect URL (custom or default)
                        $redirect_url = class_exists('Chrmrtns_KLA_Admin') ? Chrmrtns_KLA_Admin::get_redirect_url($user_id) : admin_url();
                    }
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

                    wp_redirect($tfa_verify_url);
                    exit;
                } elseif ($role_required) {
                    // User's role requires 2FA but they don't have it set up yet
                    // Check grace period before requiring 2FA
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
                        wp_redirect(add_query_arg(array('action' => 'keyless-2fa-setup', 'magic_login' => '1'), home_url()));
                        exit;
                    }
                    // Grace period still active - allow login to proceed normally
                }
            }
        }

        // If no 2FA required, proceed with normal login
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);

        // Increment successful logins counter
        $current_count = get_option('chrmrtns_kla_successful_logins', 0);
        update_option('chrmrtns_kla_successful_logins', $current_count + 1);

        // Clean up token
        delete_user_meta($user_id, 'chrmrtns_kla_login_token');
        delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');

        // Check for custom redirect from shortcode first
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET parameter for redirect, validated with esc_url_raw()
        $custom_redirect = isset($_GET['chrmrtns_kla_redirect']) ? esc_url_raw(wp_unslash($_GET['chrmrtns_kla_redirect'])) : '';

        if (!empty($custom_redirect)) {
            $redirect_url = $custom_redirect;
        } else {
            // Get redirect URL (custom or default)
            $redirect_url = class_exists('Chrmrtns_KLA_Admin') ? Chrmrtns_KLA_Admin::get_redirect_url($user_id) : admin_url();
        }

        $redirect_url = apply_filters('chrmrtns_kla_after_login_redirect', $redirect_url, $user_id);
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Validate login token
     */
    private function validate_login_token($user_id, $provided_token) {
        // Try new database system first
        if (class_exists('Chrmrtns_KLA_Database')) {
            $database = new Chrmrtns_KLA_Database();

            // Log the login attempt
            $user = get_user_by('ID', $user_id);
            $user_email = $user ? $user->user_email : '';

            if ($database->validate_login_token($user_id, $provided_token)) {
                // Log successful login
                $database->log_login_attempt($user_id, $user_email, 'success', $provided_token);
                return true;
            } else {
                // Log failed login
                $database->log_login_attempt($user_id, $user_email, 'failed', $provided_token, 'Invalid or expired token');
                return false;
            }
        }

        // Fallback to legacy user meta system
        $stored_token = get_user_meta($user_id, 'chrmrtns_kla_login_token', true);
        $expiration = get_user_meta($user_id, 'chrmrtns_kla_login_token_expiration', true);

        // Check if token exists
        if (empty($stored_token) || empty($expiration)) {
            return false;
        }

        // Check expiration
        if (time() > $expiration) {
            delete_user_meta($user_id, 'chrmrtns_kla_login_token');
            delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');
            return false;
        }

        // Use hash_equals to prevent timing attacks
        if (!hash_equals($stored_token, $provided_token)) {
            return false;
        }

        return true;
    }
    
    /**
     * Get current page URL
     */
    private function get_current_page_url() {
        global $wp;
        
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']));
            
            // Remove existing chrmrtns parameters to avoid accumulation
            $clean_uri = remove_query_arg(array(
                'chrmrtns_kla_token',
                'chrmrtns_kla_user_id',
                'chrmrtns_kla_error_token',
                'chrmrtns_kla_adminapp_error'
            ), $request_uri);
            
            return home_url($clean_uri);
        }
        
        return home_url(add_query_arg(array(), $wp->request));
    }
    
    /**
     * Prevent user enumeration
     * Blocks common methods attackers use to discover usernames
     */
    public function prevent_user_enumeration() {
        // Block REST API user endpoints
        add_filter('rest_endpoints', array($this, 'block_rest_user_endpoints'));

        // Block author archive access
        add_action('template_redirect', array($this, 'block_author_archives'));

        // Remove login error messages
        add_filter('login_errors', array($this, 'remove_login_errors'));

        // Remove comment author classes that expose usernames
        add_filter('comment_class', array($this, 'remove_comment_author_class'));

        // Block oembed user data
        add_filter('oembed_response_data', array($this, 'remove_oembed_author_data'), 10, 2);
    }

    /**
     * Block REST API user endpoints
     */
    public function block_rest_user_endpoints($endpoints) {
        if (!is_user_logged_in()) {
            if (isset($endpoints['/wp/v2/users'])) {
                unset($endpoints['/wp/v2/users']);
            }
            if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
                unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
            }
        }
        return $endpoints;
    }

    /**
     * Block author archive access
     */
    public function block_author_archives() {
        if (is_admin()) {
            return;
        }

        // Block author archives
        if (is_author()) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }

        // Block ?author=N queries
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Blocking enumeration attack, not processing form data
        if (isset($_GET['author']) && !empty($_GET['author'])) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }
    }

    /**
     * Remove login error messages
     */
    public function remove_login_errors($error) {
        return '';
    }

    /**
     * Remove comment author classes that expose usernames
     */
    public function remove_comment_author_class($classes) {
        foreach ($classes as $key => $class) {
            if (strpos($class, 'comment-author-') === 0) {
                unset($classes[$key]);
            }
        }
        return $classes;
    }

    /**
     * Remove author data from oembed responses
     */
    public function remove_oembed_author_data($data, $post) {
        unset($data['author_name']);
        unset($data['author_url']);
        return $data;
    }

    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        // Enqueue legacy styles for backward compatibility
        if (file_exists(CHRMRTNS_KLA_PLUGIN_DIR . '/assets/css/style-front-end.css')) {
            wp_register_style('chrmrtns_frontend_stylesheet', CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/style-front-end.css', array(), CHRMRTNS_KLA_VERSION);
            wp_enqueue_style('chrmrtns_frontend_stylesheet');
        }

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
                array('chrmrtns_frontend_stylesheet'), // Load after the base stylesheet
                CHRMRTNS_KLA_VERSION,
                'all'
            );
        }
    }


    /**
     * Add magic login field to wp-login.php - positioned after main form with CSS order
     */
    public function chrmrtns_kla_add_wp_login_field() {
        // Check emergency disable first
        if ($this->is_emergency_disabled()) {
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

        $random_id = wp_generate_password(8, false);
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
     */
    public function chrmrtns_kla_handle_wp_login_submission() {
        // Check if emergency disabled - return early if so
        if ($this->is_emergency_disabled()) {
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

        // Process the request
        $this->handle_login_request();

        // Redirect back to wp-login.php with success message
        wp_redirect(add_query_arg(array(
            'chrmrtns_kla_sent' => '1'
        ), wp_login_url()));
        exit;
    }
}