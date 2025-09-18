<?php
/**
 * Admin functionality for Keyless Auth
 * 
 * @since 2.0.1
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Chrmrtns_KLA_Admin {
    
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
            __('Keyless Auth', 'keyless-auth'),
            __('Keyless Auth', 'keyless-auth'),
            'manage_options',
            'keyless-auth',
            array($this, 'main_page'),
            'dashicons-shield-alt',
            30
        );
        
        add_submenu_page(
            'keyless-auth',
            __('Email Templates', 'keyless-auth'),
            __('Templates', 'keyless-auth'),
            'manage_options',
            'keyless-auth-settings',
            array($this, 'settings_page')
        );
        
        add_submenu_page(
            'keyless-auth',
            __('SMTP Settings', 'keyless-auth'),
            __('SMTP', 'keyless-auth'),
            'manage_options',
            'chrmrtns-kla-smtp-settings',
            array($this, 'smtp_settings_page')
        );
        
        add_submenu_page(
            'keyless-auth',
            __('Mail Logs', 'keyless-auth'),
            __('Mail Logs', 'keyless-auth'),
            'manage_options',
            'chrmrtns-mail-logs',
            array($this, 'mail_logs_page')
        );
    }
    
    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_email_template', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_custom_email_body', array(
            'sanitize_callback' => 'wp_kses_post'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_custom_email_styles', array(
            'sanitize_callback' => 'wp_strip_all_tags'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_button_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_button_hover_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_link_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_link_hover_color', array(
            'sanitize_callback' => 'sanitize_hex_color'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_button_text_color', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        register_setting('chrmrtns_kla_settings_group', 'chrmrtns_kla_button_hover_text_color', array(
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        add_action('wp_ajax_chrmrtns_kla_save_settings', array($this, 'save_settings'));
        add_action('admin_post_chrmrtns_kla_save_settings', array($this, 'save_settings'));
        
        // Handle form submission on settings page load
        add_action('admin_init', array($this, 'handle_form_submission'));
    }
    
    /**
     * Main admin page
     */
    public function main_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
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
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
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
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }

        // Handle test email and cache clearing if SMTP class is loaded
        if (class_exists('Chrmrtns_KLA_SMTP')) {
            $smtp = new Chrmrtns_KLA_SMTP();
            $smtp->handle_test_email_submission();
            $smtp->handle_cache_clear();
            settings_errors('chrmrtns_kla_smtp_test_email');
            settings_errors('chrmrtns_kla_smtp_cache');
        }
        
        ?>
        <div class="wrap chrmrtns-wrap">
            <h1><?php esc_html_e('SMTP Settings', 'keyless-auth'); ?></h1>
            <p><?php esc_html_e('Configure SMTP settings to ensure reliable email delivery for your passwordless login emails.', 'keyless-auth'); ?></p>
            
            <form action='options.php' method='post'>
                <?php
                settings_fields('chrmrtns_kla_smtp_settings_group');
                do_settings_sections('chrmrtns-kla-smtp-settings');
                submit_button();
                ?>
            </form>
            
            <?php if (class_exists('Chrmrtns_KLA_SMTP')): ?>
                <hr />
                <h2><?php esc_html_e('Send Test Email', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Send a test email to verify your SMTP configuration is working correctly.', 'keyless-auth'); ?></p>
                <form method="post">
                    <?php wp_nonce_field('chrmrtns_kla_smtp_send_test_email_action', 'chrmrtns_kla_smtp_send_test_email_nonce'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Test Email Address', 'keyless-auth'); ?></th>
                            <td>
                                <input type="email" name="test_email_address" value="<?php echo esc_attr(get_option('admin_email')); ?>" size="50">
                                <p class="description"><?php esc_html_e('Email address to send the test email to. Defaults to admin email.', 'keyless-auth'); ?></p>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button(__('Send Test Email', 'keyless-auth'), 'secondary', 'chrmrtns_kla_smtp_send_test_email'); ?>
                </form>

                <hr />
                <h2><?php esc_html_e('Clear Settings Cache', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('If your SMTP settings are not updating properly, clear the cache to force the plugin to reload settings from the database.', 'keyless-auth'); ?></p>
                <form method="post">
                    <?php wp_nonce_field('chrmrtns_kla_smtp_clear_cache_action', 'chrmrtns_kla_smtp_clear_cache_nonce'); ?>
                    <?php submit_button(__('Clear SMTP Cache', 'keyless-auth'), 'secondary', 'chrmrtns_kla_smtp_clear_cache'); ?>
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
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }
        
        // Render mail logs page if Mail Logger class is loaded
        if (class_exists('Chrmrtns_KLA_Mail_Logger')) {
            $mail_logger = new Chrmrtns_KLA_Mail_Logger();
            $mail_logger->render_mail_logs_page();
        }
    }
    
    /**
     * Render main page content
     */
    private function render_main_content() {
        ?>
        <div class="chrmrtns-badge"></div>
        <h1><?php esc_html_e('Keyless Auth', 'keyless-auth'); ?> <small>v.<?php echo esc_html(CHRMRTNS_KLA_VERSION); ?></small></h1>
        <p class="chrmrtns-text">
            <?php 
            $successful_logins = get_option('chrmrtns_kla_successful_logins', 0);
            echo wp_kses(
                sprintf(
                    /* translators: %d: number of successful passwordless logins */
                    __('<p>A front-end login form without a password.</p><p><strong style="font-size: 16px; color:#d54e21;">%d</strong> successful logins so far.</p>', 'keyless-auth'), 
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
            <?php esc_html_e('One time password for WordPress', 'keyless-auth'); ?>
        </div>
        
        <div class="chrmrtns-row chrmrtns-2-col">
            <div>
                <h2><?php esc_html_e('[keyless-auth] shortcode', 'keyless-auth'); ?></h2>
                <p><?php echo wp_kses(
                    __('Just place <strong class="nowrap">[keyless-auth]</strong> shortcode in a page or a widget and you\'re good to go.', 'keyless-auth'),
                    array('strong' => array('class' => array()))
                ); ?></p>
                <p><textarea class="chrmrtns-shortcode textarea" readonly onclick="this.select();" style="width: 100%; height: 60px; padding: 10px;">[keyless-auth]</textarea></p>
            </div>
            
            <div>
                <h2><?php esc_html_e('An alternative to passwords', 'keyless-auth'); ?></h2>
                <ul>
                    <li><?php esc_html_e('Visual email template selection with live previews', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('WYSIWYG email editor with HTML support', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Advanced color controls (hex, RGB, HSL, HSLA)', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Separate button and link color customization', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Enhanced security with timing attack protection', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('SMTP configuration for reliable email delivery', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Comprehensive email logging and monitoring', 'keyless-auth'); ?></li>
                </ul>
                <p><?php echo wp_kses(
                    __('Keyless Auth <strong>does not</strong> replace the default login functionality in WordPress. Instead you can have the two work in parallel.', 'keyless-auth'),
                    array('strong' => array())
                ); ?></p>
            </div>
        </div>
        
        <hr>
        
        <h2><?php esc_html_e('Advanced Email Features', 'keyless-auth'); ?></h2>
        <div class="chrmrtns-row chrmrtns-2-col">
            <div>
                <h3><?php esc_html_e('SMTP Configuration', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('Configure SMTP settings to ensure reliable email delivery with support for major providers like Gmail, Outlook, Mailgun, and SendGrid.', 'keyless-auth'); ?></p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=chrmrtns-kla-smtp-settings')); ?>" class="button button-primary"><?php esc_html_e('Configure SMTP', 'keyless-auth'); ?></a></p>
            </div>
            <div>
                <h3><?php esc_html_e('Mail Logging', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('Track and monitor all emails sent from your WordPress site with detailed logging including timestamps, recipients, and content.', 'keyless-auth'); ?></p>
                <p><a href="<?php echo esc_url(admin_url('admin.php?page=chrmrtns-mail-logs')); ?>" class="button button-primary"><?php esc_html_e('View Mail Logs', 'keyless-auth'); ?></a></p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings form
     */
    private function render_settings_form() {
        if (class_exists('Chrmrtns_KLA_Email_Templates')) {
            $email_templates = new Chrmrtns_KLA_Email_Templates();
            $email_templates->render_settings_page();
        }
    }
    
    /**
     * Handle notification dismiss
     */
    public function handle_notification_dismiss() {
        if (isset($_GET['chrmrtns_kla_learn_more_dismiss_notification']) && isset($_GET['_wpnonce'])) {
            if (wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'chrmrtns_kla_learn_more_dismiss_notification')) {
                update_option('chrmrtns_kla_learn_more_dismiss_notification', true);
                wp_redirect(remove_query_arg(array('chrmrtns_kla_learn_more_dismiss_notification', '_wpnonce')));
                exit;
            }
        }
    }
    
    /**
     * Display admin notice
     */
    public function display_admin_notice() {
        if (!get_option('chrmrtns_kla_learn_more_dismiss_notification')) {
            $learn_more_url = admin_url('admin.php?page=keyless-auth');
            $dismiss_url = wp_nonce_url(
                add_query_arg('chrmrtns_kla_learn_more_dismiss_notification', '0'),
                'chrmrtns_kla_learn_more_dismiss_notification'
            );
            ?>
            <div class="updated" style="max-width: 800px;">
                <p>
                    <?php esc_html_e('Use [keyless-auth] shortcode in your pages or widgets.', 'keyless-auth'); ?>
                    <a href="<?php echo esc_url($learn_more_url); ?>"><?php esc_html_e('Learn more.', 'keyless-auth'); ?></a>
                    <a href="<?php echo esc_url($dismiss_url); ?>" class="chrmrtns-dismiss-notification" style="float:right;margin-left:20px;">
                        <?php esc_html_e('Dismiss', 'keyless-auth'); ?>
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
            'toplevel_page_keyless-auth',
            'keyless-auth_page_keyless-auth-settings',
            'keyless-auth_page_chrmrtns-kla-smtp-settings',
            'keyless-auth_page_chrmrtns-mail-logs'
        );
        
        if (in_array($hook, $allowed_pages)) {
            // Enqueue main admin stylesheet
            wp_enqueue_style('chrmrtns_kla_admin_stylesheet', CHRMRTNS_KLA_PLUGIN_URL . 'assets/style-back-end.css', array(), CHRMRTNS_KLA_VERSION);
            
            // Enqueue additional admin styles
            wp_enqueue_style('chrmrtns_kla_admin_style', CHRMRTNS_KLA_PLUGIN_URL . 'assets/admin-style.css', array(), CHRMRTNS_KLA_VERSION);
            
            // Add logo with correct path and notice width styling
            $inline_css = '
                .chrmrtns-badge { background: url(' . CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png) center 0px no-repeat #007bff; }
                #setting-error-settings_saved { max-width: 800px; }
            ';
            wp_add_inline_style('chrmrtns_kla_admin_stylesheet', $inline_css);
            
            // Enqueue admin JavaScript
            wp_enqueue_script('chrmrtns_kla_admin_script', CHRMRTNS_KLA_PLUGIN_URL . 'assets/admin-script.js', array('jquery'), CHRMRTNS_KLA_VERSION, true);
            
            // Localize script for AJAX
            wp_localize_script('chrmrtns_kla_admin_script', 'chrmrtns_kla_ajax', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('chrmrtns_kla_ajax_nonce')
            ));
            
            // Enqueue editor scripts for settings page
            if ($hook === 'keyless-auth_page_keyless-auth-settings') {
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
        if (isset($_POST['chrmrtns_kla_settings_nonce']) && isset($_POST['submit'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: Form submission detected, calling save_settings');
            }
            $this->save_settings();
        } elseif (isset($_POST['chrmrtns_kla_settings_nonce'])) {
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
        if (!isset($_POST['chrmrtns_kla_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_settings_nonce'])), 'chrmrtns_kla_settings_save')) {
            wp_die(esc_html__('Security check failed.', 'keyless-auth'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'keyless-auth'));
        }
        
        // Save settings directly here instead of delegating  
        if (isset($_POST['chrmrtns_kla_email_template'])) {
            update_option('chrmrtns_kla_email_template', sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_email_template'])));
        }
        
        if (isset($_POST['chrmrtns_kla_button_color'])) {
            update_option('chrmrtns_kla_button_color', sanitize_hex_color(wp_unslash($_POST['chrmrtns_kla_button_color'])));
        }
        
        if (isset($_POST['chrmrtns_kla_button_hover_color'])) {
            update_option('chrmrtns_kla_button_hover_color', sanitize_hex_color(wp_unslash($_POST['chrmrtns_kla_button_hover_color'])));
        }
        
        if (isset($_POST['chrmrtns_kla_link_color'])) {
            update_option('chrmrtns_kla_link_color', sanitize_hex_color(wp_unslash($_POST['chrmrtns_kla_link_color'])));
        }
        
        if (isset($_POST['chrmrtns_kla_link_hover_color'])) {
            update_option('chrmrtns_kla_link_hover_color', sanitize_hex_color(wp_unslash($_POST['chrmrtns_kla_link_hover_color'])));
        }
        
        if (isset($_POST['chrmrtns_kla_custom_email_body'])) {
            update_option('chrmrtns_kla_custom_email_body', wp_kses_post(wp_unslash($_POST['chrmrtns_kla_custom_email_body'])));
        }
        
        if (isset($_POST['chrmrtns_kla_custom_email_styles'])) {
            update_option('chrmrtns_kla_custom_email_styles', wp_strip_all_tags(wp_unslash($_POST['chrmrtns_kla_custom_email_styles'])));
        }
        
        if (isset($_POST['chrmrtns_kla_button_text_color'])) {
            $text_color = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_button_text_color']));
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: Button text color received: ' . sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_button_text_color'])) . ' -> sanitized: ' . $text_color);
            }
            // Fallback to default if not a valid color format
            if (empty($text_color)) {
                $text_color = '#ffffff';
            }
            update_option('chrmrtns_kla_button_text_color', $text_color);
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: Button text color saved as: ' . $text_color);
            }
        } else {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('CHRMRTNS: chrmrtns_kla_button_text_color not found in POST data');
            }
        }
        
        if (isset($_POST['chrmrtns_kla_button_hover_text_color'])) {
            $hover_text_color = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_button_hover_text_color']));
            // Fallback to default if not a valid color format  
            if (empty($hover_text_color)) {
                $hover_text_color = '#ffffff';
            }
            update_option('chrmrtns_kla_button_hover_text_color', $hover_text_color);
        }
        
        // Debug: Check what was actually saved
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('CHRMRTNS: Final saved values - button_text_color: ' . get_option('chrmrtns_kla_button_text_color', 'NOT_SET'));
            error_log('CHRMRTNS: Final saved values - button_hover_text_color: ' . get_option('chrmrtns_kla_button_hover_text_color', 'NOT_SET'));
        }
        
        // Show success message
        add_settings_error('chrmrtns_kla_settings', 'settings_saved', __('Settings saved successfully.', 'keyless-auth'), 'updated');
    }
}