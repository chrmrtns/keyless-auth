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
        add_action('template_redirect', array($this, 'handle_form_submission'));
        add_shortcode('keyless-auth', array($this, 'render_login_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));

        // wp-login.php integration - only add hooks if enabled
        if (get_option('chrmrtns_kla_enable_wp_login', '0') === '1') {
            add_action('login_form', array($this, 'chrmrtns_kla_add_wp_login_field'));
            add_action('authenticate', array($this, 'chrmrtns_kla_handle_wp_login_request'), 20, 3);
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
    public function render_login_form() {
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
            $this->render_login_form_html();
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render login form HTML
     */
    private function render_login_form_html() {
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
        
        ?>
        <form method="post" class="chrmrtns-form">
            <p>
                <label for="user_email_username"><?php echo esc_html(apply_filters('chrmrtns_kla_change_form_label', $login_label)); ?></label><br>
                <input type="text" name="user_email_username" id="user_email_username" class="input" value="" size="20" required />
            </p>
            <?php wp_nonce_field('chrmrtns_kla_keyless_login_request', 'nonce', false); ?>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php esc_html_e('Send me the link', 'keyless-auth'); ?>" />
            </p>
        </form>
        <?php
    }
    
    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in handle_login_request()
        if (isset($_POST['user_email_username']) && isset($_POST['nonce'])) {
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
        
        // Create login URL
        $login_url = add_query_arg(array(
            'chrmrtns_kla_token' => $token,
            'chrmrtns_kla_user_id' => $user_id
        ), $this->get_current_page_url());
        
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

                // If user has 2FA enabled OR their role requires it, redirect to 2FA verification
                if (($user_settings && $user_settings->totp_enabled) || $role_required) {
                    // Get redirect URL (custom or default)
                    $redirect_url = class_exists('Chrmrtns_KLA_Admin') ? Chrmrtns_KLA_Admin::get_redirect_url($user_id) : admin_url();
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

        // Get redirect URL (custom or default)
        $redirect_url = class_exists('Chrmrtns_KLA_Admin') ? Chrmrtns_KLA_Admin::get_redirect_url($user_id) : admin_url();
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
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (file_exists(CHRMRTNS_KLA_PLUGIN_DIR . '/assets/css/style-front-end.css')) {
            wp_register_style('chrmrtns_frontend_stylesheet', CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/style-front-end.css', array(), CHRMRTNS_KLA_VERSION);
            wp_enqueue_style('chrmrtns_frontend_stylesheet');
        }
    }

    /**
     * Add magic login field to wp-login.php
     */
    public function chrmrtns_kla_add_wp_login_field() {
        // Check emergency disable first
        if ($this->is_emergency_disabled()) {
            return;
        }

        // Only add field if the option is enabled
        if (get_option('chrmrtns_kla_enable_wp_login', '0') !== '1') {
            return;
        }
        ?>
        <div id="chrmrtns-kla-wp-login-wrapper" style="margin: 15px 0; padding: 10px; border: 1px solid #ddd; border-radius: 4px; background: #f9f9f9;">
            <h3 style="margin: 0 0 10px 0; font-size: 14px; color: #666;"><?php esc_html_e('Magic Login (No Password Required)', 'keyless-auth'); ?></h3>
            <p style="margin: 0 0 10px 0; font-size: 12px; color: #666;">
                <?php esc_html_e('Enter your email address to receive a secure login link.', 'keyless-auth'); ?>
            </p>

            <form id="chrmrtns-kla-wp-login-form" method="post" action="">
                <?php wp_nonce_field('chrmrtns_kla_wp_login', 'chrmrtns_kla_wp_login_nonce'); ?>
                <input type="hidden" name="chrmrtns_kla_wp_login_request" value="1" />

                <p>
                    <label for="chrmrtns_kla_wp_login_email" style="display: block; margin-bottom: 5px; font-weight: bold;">
                        <?php esc_html_e('Email or Username:', 'keyless-auth'); ?>
                    </label>
                    <input type="text"
                           id="chrmrtns_kla_wp_login_email"
                           name="chrmrtns_kla_wp_login_email"
                           class="input"
                           size="20"
                           style="width: 100%;"
                           placeholder="<?php esc_attr_e('Enter email or username', 'keyless-auth'); ?>"
                           required />
                </p>

                <p>
                    <input type="submit"
                           name="chrmrtns_kla_wp_login_submit"
                           id="chrmrtns_kla_wp_login_submit"
                           class="button button-primary button-large"
                           value="<?php esc_attr_e('Send Magic Link', 'keyless-auth'); ?>"
                           style="width: 100%;" />
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Handle magic login request from wp-login.php
     */
    public function chrmrtns_kla_handle_wp_login_request($user, $username, $password) {
        // Check if emergency disabled - return early if so
        if ($this->is_emergency_disabled()) {
            return $user;
        }

        // Only handle if this is our magic login request
        if (!isset($_POST['chrmrtns_kla_wp_login_request']) ||
            !isset($_POST['chrmrtns_kla_wp_login_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_wp_login_nonce'])), 'chrmrtns_kla_wp_login')) {
            return $user;
        }

        // Get the email/username from our custom field
        if (!isset($_POST['chrmrtns_kla_wp_login_email'])) {
            return $user;
        }
        $user_input = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_wp_login_email']));

        if (empty($user_input)) {
            return new WP_Error('empty_field', __('Please enter your email address or username.', 'keyless-auth'));
        }

        // Process the magic login request (reuse existing logic)
        // Set the POST data that handle_login_request expects
        $_POST['user_email_username'] = $user_input;
        $_POST['chrmrtns_kla_login_nonce'] = wp_create_nonce('chrmrtns_kla_login');

        ob_start();
        $this->handle_login_request();
        $output = ob_get_clean();

        // Check if there was an error stored
        $error = get_option('chrmrtns_kla_login_request_error');
        if (is_wp_error($error)) {
            return $error;
        }

        // Don't actually log the user in yet - they need to click the email link
        // Return an error that will be displayed as a success message
        return new WP_Error('magic_link_sent',
            __('Magic login link sent! Check your email and click the link to login.', 'keyless-auth')
        );
    }
}