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
            'passwordless-auth-settings',
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
            'sanitize_callback' => 'wp_kses_post'
        ));
        register_setting('chrmrtns_settings_group', 'chrmrtns_custom_email_styles', array(
            'sanitize_callback' => 'wp_strip_all_tags'
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
        register_setting('chrmrtns_settings_group', 'chrmrtns_button_text_color', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('chrmrtns_settings_group', 'chrmrtns_button_hover_text_color', array(
            'sanitize_callback' => 'sanitize_text_field'
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
            echo wp_kses(
                sprintf(
                    /* translators: %d: number of successful passwordless logins */
                    __('<p>A front-end login form without a password.</p><p><strong style="font-size: 16px; color:#d54e21;">%d</strong> successful logins so far.</p>', 'passwordless-auth'), 
                    intval($successful_logins)
                ),
                array(
                    'p' => array(),
                    'strong' => array(
                        'style' => array()
                    )
                )
            );
            ?>
        </p>
        
        <div class="chrmrtns-callout">
            <?php esc_html_e('One time password for WordPress', 'passwordless-auth'); ?>
        </div>
        
        <div class="chrmrtns-row chrmrtns-2-col">
            <div>
                <h2><?php esc_html_e('[passwordless-auth] shortcode', 'passwordless-auth'); ?></h2>
                <p><?php echo wp_kses(
                    __('Just place <strong class="nowrap">[passwordless-auth]</strong> shortcode in a page or a widget and you\'re good to go.', 'passwordless-auth'),
                    array('strong' => array('class' => array()))
                ); ?></p>
                <p><textarea class="chrmrtns-shortcode textarea" readonly onclick="this.select();" style="width: 100%; height: 60px; padding: 10px;">[passwordless-auth]</textarea></p>
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
                <p><?php echo wp_kses(
                    __('Passwordless Authentication <strong>does not</strong> replace the default login functionality in WordPress. Instead you can have the two work in parallel.', 'passwordless-auth'),
                    array('strong' => array())
                ); ?></p>
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
            if (wp_verify_nonce($_GET['_wpnonce'], 'chrmrtns_learn_more_dismiss_notification')) {
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
            $learn_more_url = admin_url('admin.php?page=passwordless-auth');
            $dismiss_url = wp_nonce_url(
                add_query_arg('chrmrtns_learn_more_dismiss_notification', '0'),
                'chrmrtns_learn_more_dismiss_notification'
            );
            ?>
            <div class="updated" style="max-width: 800px;">
                <p>
                    <?php esc_html_e('Use [passwordless-auth] shortcode in your pages or widgets.', 'passwordless-auth'); ?>
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
            'toplevel_page_passwordless-auth',
            'passwordless-auth_page_passwordless-auth-settings',
            'passwordless-auth_page_chrmrtns-smtp-settings',
            'passwordless-auth_page_chrmrtns-mail-logs'
        );
        
        if (in_array($hook, $allowed_pages)) {
            wp_register_style('chrmrtns_admin_stylesheet', CHRMRTNS_PLUGIN_URL . 'assets/style-back-end.css', array(), CHRMRTNS_PASSWORDLESS_VERSION);
            wp_enqueue_style('chrmrtns_admin_stylesheet');
            
            // Add logo with correct path and notice width styling
            $inline_css = '
                .chrmrtns-badge { background: url(' . CHRMRTNS_PLUGIN_URL . 'assets/logo_150_150.png) center 0px no-repeat #007bff; }
                #setting-error-settings_saved { max-width: 800px; }
            ';
            wp_add_inline_style('chrmrtns_admin_stylesheet', $inline_css);
            
            // Enqueue editor scripts for settings page
            if ($hook === 'passwordless-auth_page_passwordless-auth-settings') {
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
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CHRMRTNS: handle_form_submission called - POST data: ' . print_r($_POST, true));
        }
        
        // Check if this is a settings page submission
        if (isset($_POST['chrmrtns_settings_nonce']) && isset($_POST['submit'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: Form submission detected, calling save_settings');
            }
            $this->save_settings();
        } elseif (isset($_POST['chrmrtns_settings_nonce'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: Nonce found but submit button missing');
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: No relevant POST data found');
            }
        }
    }
    
    public function save_settings() {
        if (!wp_verify_nonce($_POST['chrmrtns_settings_nonce'], 'chrmrtns_settings_save')) {
            wp_die(esc_html__('Security check failed.', 'passwordless-auth'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'passwordless-auth'));
        }
        
        // Save settings directly here instead of delegating  
        if (isset($_POST['chrmrtns_email_template'])) {
            update_option('chrmrtns_email_template', sanitize_text_field($_POST['chrmrtns_email_template']));
        }
        
        if (isset($_POST['chrmrtns_button_color'])) {
            update_option('chrmrtns_button_color', sanitize_hex_color($_POST['chrmrtns_button_color']));
        }
        
        if (isset($_POST['chrmrtns_button_hover_color'])) {
            update_option('chrmrtns_button_hover_color', sanitize_hex_color($_POST['chrmrtns_button_hover_color']));
        }
        
        if (isset($_POST['chrmrtns_link_color'])) {
            update_option('chrmrtns_link_color', sanitize_hex_color($_POST['chrmrtns_link_color']));
        }
        
        if (isset($_POST['chrmrtns_link_hover_color'])) {
            update_option('chrmrtns_link_hover_color', sanitize_hex_color($_POST['chrmrtns_link_hover_color']));
        }
        
        if (isset($_POST['chrmrtns_custom_email_body'])) {
            update_option('chrmrtns_custom_email_body', wp_kses_post($_POST['chrmrtns_custom_email_body']));
        }
        
        if (isset($_POST['chrmrtns_custom_email_styles'])) {
            update_option('chrmrtns_custom_email_styles', wp_strip_all_tags($_POST['chrmrtns_custom_email_styles']));
        }
        
        if (isset($_POST['chrmrtns_button_text_color'])) {
            $text_color = sanitize_text_field($_POST['chrmrtns_button_text_color']);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: Button text color received: ' . $_POST['chrmrtns_button_text_color'] . ' -> sanitized: ' . $text_color);
            }
            // Fallback to default if not a valid color format
            if (empty($text_color)) {
                $text_color = '#ffffff';
            }
            update_option('chrmrtns_button_text_color', $text_color);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: Button text color saved as: ' . $text_color);
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: chrmrtns_button_text_color not found in POST data');
            }
        }
        
        if (isset($_POST['chrmrtns_button_hover_text_color'])) {
            $hover_text_color = sanitize_text_field($_POST['chrmrtns_button_hover_text_color']);
            // Fallback to default if not a valid color format  
            if (empty($hover_text_color)) {
                $hover_text_color = '#ffffff';
            }
            update_option('chrmrtns_button_hover_text_color', $hover_text_color);
        }
        
        // Debug: Check what was actually saved
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CHRMRTNS: Final saved values - button_text_color: ' . get_option('chrmrtns_button_text_color', 'NOT_SET'));
            error_log('CHRMRTNS: Final saved values - button_hover_text_color: ' . get_option('chrmrtns_button_hover_text_color', 'NOT_SET'));
        }
        
        // Show success message
        add_settings_error('chrmrtns_settings', 'settings_saved', __('Settings saved successfully.', 'passwordless-auth'), 'updated');
    }
}