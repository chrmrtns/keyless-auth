<?php
/**
 * Core authentication functionality for Passwordless Auth
 * 
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_Core {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_nopriv_chrmrtns_request_login_code', array($this, 'handle_login_request'));
        add_action('wp_ajax_chrmrtns_request_login_code', array($this, 'handle_login_request'));
        add_action('init', array($this, 'handle_login_link'));
        add_shortcode('chrmrtns-passwordless-auth', array($this, 'render_login_form'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }
    
    /**
     * Render login form shortcode
     */
    public function render_login_form() {
        ob_start();
        
        $account = (isset($_POST['user_email_username'])) ? sanitize_text_field($_POST['user_email_username']) : false;
        $error_token = (isset($_GET['chrmrtns_error_token'])) ? sanitize_key($_GET['chrmrtns_error_token']) : false;
        $adminapp_error = (isset($_GET['chrmrtns_adminapp_error'])) ? sanitize_key($_GET['chrmrtns_adminapp_error']) : false;

        $sent_link = get_option('chrmrtns_login_request_error');

        if ($account && !is_wp_error($sent_link)) {
            echo '<p class="chrmrtns-box chrmrtns-success">' . apply_filters('chrmrtns_success_link_msg', __('Please check your email. You will soon receive an email with a login link.', 'chrmrtns-passwordless-auth')) . '</p>';
        } elseif (is_user_logged_in()) {
            $current_user = wp_get_current_user();
            echo '<p class="chrmrtns-box chrmrtns-alert">' . apply_filters('chrmrtns_success_login_msg', sprintf(__('You are currently logged in as %1$s. %2$s', 'chrmrtns-passwordless-auth'), '<a href="' . esc_url(get_author_posts_url($current_user->ID)) . '" title="' . esc_attr($current_user->display_name) . '">' . esc_html($current_user->display_name) . '</a>', '<a href="' . esc_url(wp_logout_url($this->get_current_page_url())) . '" title="' . __('Log out of this account', 'chrmrtns-passwordless-auth') . '">' . __('Log out', 'chrmrtns-passwordless-auth') . ' &raquo;</a>')) . '</p><!-- .alert-->';
        } else {
            if (is_wp_error($sent_link)) {
                echo '<p class="chrmrtns-box chrmrtns-error">' . esc_html(apply_filters('chrmrtns_error', $sent_link->get_error_message())) . '</p>';
            }
            if ($error_token) {
                echo '<p class="chrmrtns-box chrmrtns-error">' . apply_filters('chrmrtns_invalid_token_error', __('Your token has probably expired. Please try again.', 'chrmrtns-passwordless-auth')) . '</p>';
            }
            if ($adminapp_error) { // admin approval compatibility
                echo '<p class="chrmrtns-box chrmrtns-error">' . apply_filters('chrmrtns_admin_approval_error', __('Your account needs to be approved by an admin before you can log-in.', 'chrmrtns-passwordless-auth')) . '</p>';
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
        $login_label = __('Login with email or username', 'chrmrtns-passwordless-auth');
        
        if (is_plugin_active('profile-builder-pro/index.php') || is_plugin_active('profile-builder/index.php') || is_plugin_active('profile-builder-hobbyist/index.php')) {
            $wppb_general_options = get_option('wppb_general_settings');
            if (isset($wppb_general_options['loginWith']) && ($wppb_general_options['loginWith'] == 'email')) {
                $login_label = __('Login with email', 'chrmrtns-passwordless-auth');
            } elseif (isset($wppb_general_options['loginWith']) && ($wppb_general_options['loginWith'] == 'username')) {
                $login_label = __('Login with username', 'chrmrtns-passwordless-auth');
            }
        }
        
        ?>
        <form method="post" class="chrmrtns-form">
            <p>
                <label for="user_email_username"><?php echo apply_filters('chrmrtns_change_form_label', $login_label); ?></label><br>
                <input type="text" name="user_email_username" id="user_email_username" class="input" value="" size="20" required />
            </p>
            <?php wp_nonce_field('chrmrtns_passwordless_login_request', 'nonce', false); ?>
            <p class="submit">
                <input type="submit" name="wp-submit" id="wp-submit" class="button-primary" value="<?php _e('Send me the link', 'chrmrtns-passwordless-auth'); ?>" />
            </p>
        </form>
        <?php
    }
    
    /**
     * Handle login request
     */
    public function handle_login_request() {
        // Delete any existing error
        delete_option('chrmrtns_login_request_error');
        
        if (!isset($_POST['user_email_username'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'chrmrtns_passwordless_login_request')) {
            $error = new WP_Error('nonce_failed', __('Security check failed. Please try again.', 'chrmrtns-passwordless-auth'));
            update_option('chrmrtns_login_request_error', $error);
            return;
        }
        
        $user_email_username = sanitize_text_field($_POST['user_email_username']);
        
        // Get user by email or username
        $user = $this->get_user_by_email_or_username($user_email_username);
        
        if (!$user) {
            $error = new WP_Error('invalid_user', __('The username or email you provided do not exist. Please try again.', 'chrmrtns-passwordless-auth'));
            update_option('chrmrtns_login_request_error', $error);
            return;
        }
        
        // Check admin approval compatibility
        if ($this->is_admin_approval_required($user)) {
            wp_redirect(add_query_arg('chrmrtns_adminapp_error', '1', $this->get_current_page_url()));
            exit;
        }
        
        // Generate and send login link
        if (!$this->send_login_email($user)) {
            $error = new WP_Error('email_failed', __('There was a problem sending your email. Please try again or contact an admin.', 'chrmrtns-passwordless-auth'));
            update_option('chrmrtns_login_request_error', $error);
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
        $expiration_time = time() + apply_filters('chrmrtns_change_link_expiration', 600); // 10 minutes default
        $token = $this->generate_secure_token($user_id, $expiration_time);
        
        // Store token with user
        update_user_meta($user_id, 'chrmrtns_login_token', $token);
        update_user_meta($user_id, 'chrmrtns_login_token_expiration', $expiration_time);
        
        // Create login URL
        $login_url = add_query_arg(array(
            'chrmrtns_token' => $token,
            'chrmrtns_user_id' => $user_id
        ), $this->get_current_page_url());
        
        // Get email template
        if (class_exists('Chrmrtns_Email_Templates')) {
            $email_templates = new Chrmrtns_Email_Templates();
            $email_body = $email_templates->get_email_template($user_email, $login_url);
        } else {
            // Fallback template
            $email_body = sprintf(
                __('Hello! <br><br>Login at %s by visiting this url: <a href="%s" target="_blank">%s</a>', 'chrmrtns-passwordless-auth'),
                get_bloginfo('name'),
                esc_url($login_url),
                esc_url($login_url)
            );
        }
        
        $subject = sprintf(__('Login at %s', 'chrmrtns-passwordless-auth'), get_bloginfo('name'));
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        return wp_mail($user_email, $subject, $email_body, apply_filters('chrmrtns_email_headers', $headers));
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
        if (!isset($_GET['chrmrtns_token']) || !isset($_GET['chrmrtns_user_id'])) {
            return;
        }
        
        $token = sanitize_text_field($_GET['chrmrtns_token']);
        $user_id = intval($_GET['chrmrtns_user_id']);
        
        // Validate token
        if (!$this->validate_login_token($user_id, $token)) {
            wp_redirect(add_query_arg('chrmrtns_error_token', '1', $this->get_current_page_url()));
            exit;
        }
        
        // Log in user
        wp_set_current_user($user_id);
        wp_set_auth_cookie($user_id, true);
        
        // Increment successful logins counter
        $current_count = get_option('chrmrtns_successful_logins', 0);
        update_option('chrmrtns_successful_logins', $current_count + 1);
        
        // Clean up token
        delete_user_meta($user_id, 'chrmrtns_login_token');
        delete_user_meta($user_id, 'chrmrtns_login_token_expiration');
        
        // Redirect
        $redirect_url = apply_filters('chrmrtns_after_login_redirect', $this->get_current_page_url(), $user_id);
        wp_redirect($redirect_url);
        exit;
    }
    
    /**
     * Validate login token
     */
    private function validate_login_token($user_id, $provided_token) {
        $stored_token = get_user_meta($user_id, 'chrmrtns_login_token', true);
        $expiration = get_user_meta($user_id, 'chrmrtns_login_token_expiration', true);
        
        // Check if token exists
        if (empty($stored_token) || empty($expiration)) {
            return false;
        }
        
        // Check expiration
        if (time() > $expiration) {
            delete_user_meta($user_id, 'chrmrtns_login_token');
            delete_user_meta($user_id, 'chrmrtns_login_token_expiration');
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
            $request_uri = sanitize_text_field($_SERVER['REQUEST_URI']);
            
            // Remove existing chrmrtns parameters to avoid accumulation
            $clean_uri = remove_query_arg(array(
                'chrmrtns_token',
                'chrmrtns_user_id',
                'chrmrtns_error_token',
                'chrmrtns_adminapp_error'
            ), $request_uri);
            
            return home_url($clean_uri);
        }
        
        return home_url(add_query_arg(array(), $wp->request));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_frontend_scripts() {
        if (file_exists(CHRMRTNS_PLUGIN_DIR . '/assets/style-front-end.css')) {
            wp_register_style('chrmrtns_frontend_stylesheet', CHRMRTNS_PLUGIN_URL . 'assets/style-front-end.css', array(), CHRMRTNS_PASSWORDLESS_VERSION);
            wp_enqueue_style('chrmrtns_frontend_stylesheet');
        }
    }
}