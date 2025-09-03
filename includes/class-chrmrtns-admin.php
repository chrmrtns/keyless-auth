<?php
/**
 * Admin functionality for Passwordless Auth
 * 
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_Admin {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_init', array($this, 'handle_notification_dismiss'));
        add_action('admin_notices', array($this, 'display_admin_notice'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Passwordless Auth', 'passwordless-auth'),
            __('Passwordless Auth', 'passwordless-auth'),
            'manage_options',
            'passwordless-auth',
            array($this, 'main_page'),
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'passwordless-auth',
            __('PA Settings', 'passwordless-auth'),
            __('PA Settings', 'passwordless-auth'),
            'manage_options',
            'chrmrtns-passwordless-auth-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'passwordless-auth',
            __('SMTP Settings', 'passwordless-auth'),
            __('SMTP', 'passwordless-auth'),
            'manage_options',
            'chrmrtns-smtp-settings',
            array($this, 'smtp_settings_page')
        );
        
        add_submenu_page(
            'passwordless-auth',
            __('Mail Logs', 'passwordless-auth'),
            __('Mail Logs', 'passwordless-auth'),
            'manage_options',
            'chrmrtns-mail-logs',
            array($this, 'mail_logs_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('chrmrtns_settings_group', 'chrmrtns_email_template', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('chrmrtns_settings_group', 'chrmrtns_custom_email_body', array(
            'sanitize_callback' => array($this, 'sanitize_email_html')
        ));
        register_setting('chrmrtns_settings_group', 'chrmrtns_custom_email_styles', array(
            'sanitize_callback' => array($this, 'sanitize_css')
        ));
        register_setting('chrmrtns_settings_group', 'chrmrtns_button_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_settings_group', 'chrmrtns_button_hover_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_settings_group', 'chrmrtns_link_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_settings_group', 'chrmrtns_link_hover_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        
        add_action('wp_ajax_chrmrtns_save_settings', array($this, 'save_settings'));
        add_action('admin_post_chrmrtns_save_settings', array($this, 'save_settings'));
        
        // Handle form submission on settings page load
        add_action('admin_init', array($this, 'handle_form_submission'));
    }
    
    /**
     * Main admin page
     */
    public function main_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'passwordless-auth'));
        }
        ?>
        <div class="wrap chrmrtns-wrap">
            <?php $this->render_main_content(); ?>
        </div>
        <?php
    }
    
    /**
     * Settings page
     */
    public function settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'passwordless-auth'));
        }
        ?>
        <div class="wrap chrmrtns-wrap">
            <?php $this->render_settings_form(); ?>
        </div>
        <?php
    }
    
    /**
     * SMTP settings page
     */
    public function smtp_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'passwordless-auth'));
        }
        
        // Handle test email if SMTP class is loaded
        if (class_exists('Chrmrtns_SMTP')) {
            $smtp = new Chrmrtns_SMTP();
            $smtp->handle_test_email_submission();
            settings_errors('chrmrtns_smtp_test_email');
        }
        
        ?>
        <div class="wrap chrmrtns-wrap">
            <h1><?php esc_html_e('SMTP Settings', 'passwordless-auth'); ?></h1>
            <p><?php esc_html_e('Configure SMTP settings to ensure reliable email delivery for your passwordless login emails.', 'passwordless-auth'); ?></p>
            
            <form action='options.php' method='post'>
                <?php
                settings_fields('chrmrtns_smtp_settings_group');
                do_settings_sections('chrmrtns-smtp-settings');
                submit_button();
                ?>
            </form>
            
            <?php if (class_exists('Chrmrtns_SMTP')): ?>
                <hr />
                <h2><?php esc_html_e('Send Test Email', 'passwordless-auth'); ?></h2>
                <p><?php esc_html_e('Send a test email to verify your SMTP configuration is working correctly.', 'passwordless-auth'); ?></p>
                <form method="post">
                    <?php wp_nonce_field('chrmrtns_smtp_send_test_email_action', 'chrmrtns_smtp_send_test_email_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Test Email Address', 'passwordless-auth'); ?></th>
                            <td>
                                <input type="email" name="test_email_address" value="<?php echo esc_attr(get_option('admin_email')); ?>" size="50">
                                <p class="description"><?php esc_html_e('Email address to send the test email to. Defaults to admin email.', 'passwordless-auth'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Send Test Email', 'passwordless-auth'), 'secondary', 'chrmrtns_smtp_send_test_email'); ?>
                </form>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Mail logs page
     */
    public function mail_logs_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'passwordless-auth'));
        }
        
        // Render mail logs page if Mail Logger class is loaded
        if (class_exists('Chrmrtns_Mail_Logger')) {
            $mail_logger = new Chrmrtns_Mail_Logger();
            $mail_logger->render_mail_logs_page();
        }
    }
    
    /**
     * Render main page content
     */
    private function render_main_content() {
        ?>
        <div class="chrmrtns-badge"></div>
        <h1><?php esc_html_e('Passwordless Auth', 'passwordless-auth'); ?> <small>v.<?php echo esc_html(CHRMRTNS_PASSWORDLESS_VERSION); ?></small></h1>
        <p class="chrmrtns-text">
            <?php 
            $successful_logins = get_option('chrmrtns_successful_logins', 0);
            printf(
                /* translators: %d: number of successful passwordless logins */
                esc_html__('<p>A front-end login form without a password.</p><p><strong style="font-size: 16px; color:#d54e21;">%d</strong> successful logins so far.</p>', 'passwordless-auth'), 
                esc_html($successful_logins)
            );
            ?>
        </p>
        
        <div class="chrmrtns-callout">
            <?php esc_html_e('One time password for WordPress', 'passwordless-auth'); ?>
        </div>
        
        <div class="chrmrtns-row chrmrtns-2-col">
            <div>
                <h2><?php esc_html_e('[chrmrtns-passwordless-auth] shortcode', 'passwordless-auth'); ?></h2>
                <p><?php esc_html_e('Just place <strong class="nowrap">[chrmrtns-passwordless-auth]</strong> shortcode in a page or a widget and you\'re good to go.', 'passwordless-auth'); ?></p>
                <p><textarea class="chrmrtns-shortcode textarea" readonly onclick="this.select();" style="width: 100%; height: 60px; padding: 10px;">[chrmrtns-passwordless-auth]</textarea></p>
            </div>
            
            <div>
                <h2><?php esc_html_e('An alternative to passwords', 'passwordless-auth'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Visual email template selection with live previews', 'passwordless-auth'); ?></li>
                    <li><?php esc_html_e('WYSIWYG email editor with HTML support', 'passwordless-auth'); ?></li>
                    <li><?php esc_html_e('Advanced color controls (hex, RGB, HSL, HSLA)', 'passwordless-auth'); ?></li>
                    <li><?php esc_html_e('Separate button and link color customization', 'passwordless-auth'); ?></li>
                    <li><?php esc_html_e('Enhanced security with timing attack protection', 'passwordless-auth'); ?></li>
                    <li><?php esc_html_e('SMTP configuration for reliable email delivery', 'passwordless-auth'); ?></li>
                    <li><?php esc_html_e('Comprehensive email logging and monitoring', 'passwordless-auth'); ?></li>
                </ul>
                <p><?php esc_html_e('Passwordless Authentication <strong>does not</strong> replace the default login functionality in WordPress. Instead you can have the two work in parallel.', 'passwordless-auth'); ?></p>
            </div>
        </div>
        
        <hr>
        
        <h2><?php esc_html_e('Advanced Email Features', 'passwordless-auth'); ?></h2>
        <div class="chrmrtns-row chrmrtns-2-col">
            <div>
                <h3><?php esc_html_e('SMTP Configuration', 'passwordless-auth'); ?></h3>
                <p><?php esc_html_e('Configure SMTP settings to ensure reliable email delivery with support for major providers like Gmail, Outlook, Mailgun, and SendGrid.', 'passwordless-auth'); ?></p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=chrmrtns-smtp-settings')); ?>" class="button button-primary"><?php esc_html_e('Configure SMTP', 'passwordless-auth'); ?></a></p>
            </div>
            <div>
                <h3><?php esc_html_e('Mail Logging', 'passwordless-auth'); ?></h3>
                <p><?php esc_html_e('Track and monitor all emails sent from your WordPress site with detailed logging including timestamps, recipients, and content.', 'passwordless-auth'); ?></p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=chrmrtns-mail-logs')); ?>" class="button button-primary"><?php esc_html_e('View Mail Logs', 'passwordless-auth'); ?></a></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings form
     */
    private function render_settings_form() {
        if (class_exists('Chrmrtns_Email_Templates')) {
            $email_templates = new Chrmrtns_Email_Templates();
            $email_templates->render_settings_page();
        }
    }
    
    /**
     * Handle notification dismiss
     */
    public function handle_notification_dismiss() {
        if (isset($_GET['chrmrtns_learn_more_dismiss_notification']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'chrmrtns_learn_more_dismiss_notification')) {
                update_option('chrmrtns_learn_more_dismiss_notification', true);
                wp_redirect(remove_query_arg(array('chrmrtns_learn_more_dismiss_notification', '_wpnonce')));
                exit;
            }
        }
    }
    
    /**
     * Display admin notice
     */
    public function display_admin_notice() {
        if (!get_option('chrmrtns_learn_more_dismiss_notification')) {
            $learn_more_url = admin_url('admin.php?page=chrmrtns-passwordless-auth');
            $dismiss_url = wp_nonce_url(
                add_query_arg('chrmrtns_learn_more_dismiss_notification', '0'),
                'chrmrtns_learn_more_dismiss_notification'
            );
            ?>
            <div class="updated" style="max-width: 800px;">
                <p>
                    <?php esc_html_e('Use [chrmrtns-passwordless-auth] shortcode in your pages or widgets.', 'passwordless-auth'); ?>
                    <a href="<?php echo esc_url($learn_more_url); ?>"><?php esc_html_e('Learn more.', 'passwordless-auth'); ?></a>
                    <a href="<?php echo esc_url($dismiss_url); ?>" class="chrmrtns-dismiss-notification" style="float:right;margin-left:20px;">
                        <?php esc_html_e('Dismiss', 'passwordless-auth'); ?>
                    </a>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        $allowed_pages = array(
            'toplevel_page_chrmrtns-passwordless-auth',
            'passwordless-auth_page_chrmrtns-passwordless-auth-settings',
            'passwordless-auth_page_chrmrtns-smtp-settings',
            'passwordless-auth_page_chrmrtns-mail-logs'
        );
        
        if (in_array($hook, $allowed_pages)) {
            wp_register_style('chrmrtns_admin_stylesheet', CHRMRTNS_PLUGIN_URL . 'assets/style-back-end.css', array(), CHRMRTNS_PASSWORDLESS_VERSION);
            wp_enqueue_style('chrmrtns_admin_stylesheet');
            
            // Enqueue editor scripts for settings page
            if ($hook === 'passwordless-auth_page_chrmrtns-passwordless-auth-settings') {
                wp_enqueue_editor();
                wp_enqueue_media();
                wp_enqueue_script('wp-color-picker');
                wp_enqueue_style('wp-color-picker');
            }
        }
    }
    
    /**
     * Save settings via AJAX
     */
    /**
     * Handle form submission
     */
    public function handle_form_submission() {
        // Debug logging only when WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log,WordPress.PHP.DevelopmentFunctions.error_log_print_r,WordPress.Security.NonceVerification.Missing -- Debug logging when WP_DEBUG is enabled
            error_log('CHRMRTNS: handle_form_submission called - POST data: ' . print_r($_POST, true));
        }
        
        // Check if this is a settings page submission - nonce verification happens in save_settings()
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in save_settings()
        if (isset($_POST['chrmrtns_settings_nonce']) && isset($_POST['submit'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
                error_log('CHRMRTNS: Form submission detected, calling save_settings');
            }
            $this->save_settings();
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in save_settings()
        } elseif (isset($_POST['chrmrtns_settings_nonce'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
                error_log('CHRMRTNS: Nonce found but submit button missing');
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug logging when WP_DEBUG is enabled
                error_log('CHRMRTNS: No relevant POST data found');
            }
        }
    }
    
    public function save_settings() {
        if (!isset($_POST['chrmrtns_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_settings_nonce'])), 'chrmrtns_settings_save')) {
            wp_die(esc_html__('Security check failed.', 'passwordless-auth'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'passwordless-auth'));
        }
        
        // Save settings if Email Templates class is loaded
        if (class_exists('Chrmrtns_Email_Templates')) {
            $email_templates = new Chrmrtns_Email_Templates();
            $email_templates->save_settings();
        }
    }
    
    /**
     * Sanitize email HTML content
     */
    public function sanitize_email_html($html) {
        if (class_exists('Chrmrtns_Email_Templates')) {
            $email_templates = new Chrmrtns_Email_Templates();
            return $email_templates->sanitize_email_html($html);
        }
        return wp_kses_post($html);
    }
    
    /**
     * Sanitize CSS content
     */
    public function sanitize_css($css) {
        // Remove script tags and javascript
        $css = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $css);
        $css = preg_replace('/javascript:/i', '', $css);
        $css = preg_replace('/vbscript:/i', '', $css);
        $css = preg_replace('/onload/i', '', $css);
        
        // Strip all HTML tags from CSS
        $css = wp_strip_all_tags($css);
        
        return $css;
    }
}