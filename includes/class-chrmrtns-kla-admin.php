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

        add_submenu_page(
            'keyless-auth',
            __('Options', 'keyless-auth'),
            __('Options', 'keyless-auth'),
            'manage_options',
            'keyless-auth-options',
            array($this, 'options_page')
        );

        add_submenu_page(
            'keyless-auth',
            __('Help & Instructions', 'keyless-auth'),
            __('Help', 'keyless-auth'),
            'manage_options',
            'keyless-auth-help',
            array($this, 'help_page')
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

        // Options settings
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_enable_wp_login', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
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
            <h1 class="chrmrtns-header">
                <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
                <?php esc_html_e('SMTP Settings', 'keyless-auth'); ?>
            </h1>
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
        <h1 class="chrmrtns-header">
            <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
            <?php esc_html_e('Keyless Auth', 'keyless-auth'); ?>
            <small>v.<?php echo esc_html(CHRMRTNS_KLA_VERSION); ?></small>
        </h1>
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
            'keyless-auth_page_chrmrtns-mail-logs',
            'keyless-auth_page_keyless-auth-options',
            'keyless-auth_page_keyless-auth-help'
        );
        
        if (in_array($hook, $allowed_pages)) {
            // Enqueue main admin stylesheet
            wp_enqueue_style('chrmrtns_kla_admin_stylesheet', CHRMRTNS_KLA_PLUGIN_URL . 'assets/style-back-end.css', array(), CHRMRTNS_KLA_VERSION);
            
            // Enqueue additional admin styles
            wp_enqueue_style('chrmrtns_kla_admin_style', CHRMRTNS_KLA_PLUGIN_URL . 'assets/admin-style.css', array(), CHRMRTNS_KLA_VERSION);
            
            // Add updated logo styling in header and notice width styling
            $inline_css = '
                .chrmrtns-header { display: flex; align-items: center; gap: 15px; margin-bottom: 20px; }
                .chrmrtns-header-logo { width: 40px; height: 40px; border-radius: 6px; }
                .chrmrtns-header small { color: #666; font-weight: normal; margin-left: 10px; }
                #setting-error-settings_saved { max-width: 800px; }
                .chrmrtns-badge { display: none; }
                .chrmrtns_kla_card { position: relative; margin-top: 20px; padding: 1.2em 2em 1.5em; min-width: 600px; max-width: 100%; border: 1px solid #c3c4c7; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.08); background: #fff; box-sizing: border-box; border-radius: 4px; }
                .chrmrtns_kla_card h2 { margin-top: 0; color: #23282d; border-bottom: 1px solid #e1e1e1; padding-bottom: 8px; margin-bottom: 15px; }
                .chrmrtns_kla_card h3 { color: #23282d; margin-top: 20px; margin-bottom: 10px; }
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
        // Check if this is a settings page submission (either main form or reset form)
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verification happens in save_settings()
        if (isset($_POST['chrmrtns_kla_settings_nonce']) && (isset($_POST['submit']) || isset($_POST['reset_template']))) {
            $this->save_settings();
        }
    }
    
    public function save_settings() {
        if (!isset($_POST['chrmrtns_kla_settings_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_settings_nonce'])), 'chrmrtns_kla_settings_save')) {
            wp_die(esc_html__('Security check failed.', 'keyless-auth'));
        }
        
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions.', 'keyless-auth'));
        }
        
        // Handle reset template action
        if (isset($_POST['reset_custom_template'])) {
            global $wpdb;

            // Force complete reset - delete all template-related options (both old and new naming)
            delete_option('chrmrtns_kla_custom_email_body');
            delete_option('chrmrtns_kla_custom_email_styles');
            delete_option('chrmrtns_kla_email_template'); // Reset template selection too

            // Also delete any old naming convention options that might be interfering
            delete_option('chrmrtns_custom_email_body');
            delete_option('chrmrtns_custom_email_styles');
            delete_option('chrmrtns_email_template');

            // Force deletion directly from database as a fallback for orphaned options
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Necessary for cleanup of potentially orphaned legacy options
            $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name IN ('chrmrtns_kla_custom_email_body', 'chrmrtns_kla_custom_email_styles', 'chrmrtns_kla_email_template', 'chrmrtns_custom_email_body', 'chrmrtns_custom_email_styles', 'chrmrtns_email_template')");

            // Clear all caches
            wp_cache_flush();
            wp_cache_delete('alloptions', 'options');

            // Add success message
            add_settings_error('chrmrtns_kla_settings', 'template_reset',
                esc_html__('Template Reset Complete: All email template settings have been reset to defaults.', 'keyless-auth'),
                'success');
            return;
        }

        // Save settings directly here instead of delegating
        if (isset($_POST['chrmrtns_kla_email_template'])) {
            update_option('chrmrtns_kla_email_template', sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_email_template'])));
        }
        
        if (isset($_POST['chrmrtns_kla_button_color'])) {
            $color = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_button_color']));
            update_option('chrmrtns_kla_button_color', $this->sanitize_color_value($color));
        }

        if (isset($_POST['chrmrtns_kla_button_hover_color'])) {
            $color = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_button_hover_color']));
            update_option('chrmrtns_kla_button_hover_color', $this->sanitize_color_value($color));
        }

        if (isset($_POST['chrmrtns_kla_link_color'])) {
            $color = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_link_color']));
            update_option('chrmrtns_kla_link_color', $this->sanitize_color_value($color));
        }

        if (isset($_POST['chrmrtns_kla_link_hover_color'])) {
            $color = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_link_hover_color']));
            update_option('chrmrtns_kla_link_hover_color', $this->sanitize_color_value($color));
        }
        
        if (isset($_POST['chrmrtns_kla_custom_email_body'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via wp_kses below
            $email_body = wp_unslash($_POST['chrmrtns_kla_custom_email_body']);

            // Use wp_kses with email-appropriate allowed tags instead of wp_kses_post
            $allowed_tags = array(
                'div' => array('style' => array(), 'class' => array()),
                'p' => array('style' => array(), 'class' => array()),
                'h1' => array('style' => array(), 'class' => array()),
                'h2' => array('style' => array(), 'class' => array()),
                'h3' => array('style' => array(), 'class' => array()),
                'h4' => array('style' => array(), 'class' => array()),
                'h5' => array('style' => array(), 'class' => array()),
                'h6' => array('style' => array(), 'class' => array()),
                'a' => array('href' => array(), 'style' => array(), 'class' => array(), 'target' => array(), 'rel' => array()),
                'strong' => array('style' => array(), 'class' => array()),
                'em' => array('style' => array(), 'class' => array()),
                'br' => array(),
                'hr' => array('style' => array(), 'class' => array()),
                'span' => array('style' => array(), 'class' => array()),
                'img' => array('src' => array(), 'alt' => array(), 'style' => array(), 'class' => array(), 'width' => array(), 'height' => array()),
                'table' => array('style' => array(), 'class' => array(), 'border' => array(), 'cellpadding' => array(), 'cellspacing' => array()),
                'tr' => array('style' => array(), 'class' => array()),
                'td' => array('style' => array(), 'class' => array(), 'colspan' => array(), 'rowspan' => array()),
                'th' => array('style' => array(), 'class' => array(), 'colspan' => array(), 'rowspan' => array()),
                'ul' => array('style' => array(), 'class' => array()),
                'ol' => array('style' => array(), 'class' => array()),
                'li' => array('style' => array(), 'class' => array())
            );

            $sanitized_body = wp_kses($email_body, $allowed_tags);
            update_option('chrmrtns_kla_custom_email_body', $sanitized_body);
        }
        
        if (isset($_POST['chrmrtns_kla_custom_email_styles'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized via sanitize_css_content below
            $custom_styles = wp_unslash($_POST['chrmrtns_kla_custom_email_styles']);
            // Sanitize CSS content while preserving valid CSS syntax
            $sanitized_css = $this->sanitize_css_content($custom_styles);
            update_option('chrmrtns_kla_custom_email_styles', $sanitized_css);
        }

        if (isset($_POST['chrmrtns_kla_button_text_color'])) {
            $text_color = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_button_text_color']));
            update_option('chrmrtns_kla_button_text_color', $this->sanitize_color_value($text_color));
        }

        if (isset($_POST['chrmrtns_kla_button_hover_text_color'])) {
            $hover_text_color = sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_button_hover_text_color']));
            update_option('chrmrtns_kla_button_hover_text_color', $this->sanitize_color_value($hover_text_color));
        }
        
        
        // Clear any option caches to ensure fresh data
        wp_cache_delete('chrmrtns_kla_custom_email_body', 'options');
        wp_cache_delete('chrmrtns_kla_custom_email_styles', 'options');
        wp_cache_delete('chrmrtns_kla_email_template', 'options');

        // Also clear any persistent caches
        wp_cache_flush();

        // Show success message
        add_settings_error('chrmrtns_kla_settings', 'settings_saved', __('Settings saved successfully.', 'keyless-auth'), 'updated');
    }

    /**
     * Sanitize color value (supports hex, rgb, rgba, hsl, hsla, and named colors)
     */
    private function sanitize_color_value($color) {
        $color = sanitize_text_field($color);

        if (empty($color)) {
            return '';
        }

        // Check for hex color
        if (preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $color)) {
            return $color;
        }

        // Check for RGB/RGBA
        if (preg_match('/^rgba?\(\s*\d+\s*,\s*\d+\s*,\s*\d+\s*(,\s*[\d.]+)?\s*\)$/i', $color)) {
            return $color;
        }

        // Check for HSL/HSLA
        if (preg_match('/^hsla?\(\s*\d+\s*,\s*\d+%\s*,\s*\d+%\s*(,\s*[\d.]+)?\s*\)$/i', $color)) {
            return $color;
        }

        // Check for named colors (basic set)
        $named_colors = array('white', 'black', 'red', 'green', 'blue', 'yellow', 'orange', 'purple', 'pink', 'brown', 'gray', 'grey');
        if (in_array(strtolower($color), $named_colors, true)) {
            return strtolower($color);
        }

        // Try WordPress sanitize_hex_color as fallback
        $hex_color = sanitize_hex_color($color);
        if ($hex_color) {
            return $hex_color;
        }

        // Return empty if no valid format found
        return '';
    }

    /**
     * Sanitize CSS content while preserving valid CSS syntax
     */
    private function sanitize_css_content($css) {
        if (empty($css)) {
            return '';
        }

        // Remove potentially dangerous patterns while preserving CSS
        $css = wp_strip_all_tags($css); // Remove any HTML tags
        $css = preg_replace('/javascript:/i', '', $css); // Remove javascript: protocols
        $css = preg_replace('/expression\s*\(/i', '', $css); // Remove CSS expressions
        $css = preg_replace('/@import/i', '', $css); // Remove @import statements
        $css = preg_replace('/behavior\s*:/i', '', $css); // Remove IE behavior
        $css = preg_replace('/binding\s*:/i', '', $css); // Remove binding

        return sanitize_textarea_field($css);
    }

    /**
     * Options page
     */
    public function options_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }

        // Handle form submission
        if (isset($_POST['submit_options']) && isset($_POST['chrmrtns_kla_options_nonce']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['chrmrtns_kla_options_nonce'])), 'chrmrtns_kla_options_action')) {
            $enable_wp_login = isset($_POST['chrmrtns_kla_enable_wp_login']) ? '1' : '0';
            update_option('chrmrtns_kla_enable_wp_login', $enable_wp_login);

            add_settings_error('chrmrtns_kla_options', 'settings_updated', __('Options saved successfully!', 'keyless-auth'), 'updated');
        }

        settings_errors('chrmrtns_kla_options');

        $enable_wp_login = get_option('chrmrtns_kla_enable_wp_login', '0');
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
                                <?php esc_html_e('Add a magic login field to the WordPress login page (wp-login.php).', 'keyless-auth'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php submit_button(__('Save Options', 'keyless-auth'), 'primary', 'submit_options'); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Help page
     */
    public function help_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }
        ?>
        <div class="wrap">
            <h1 class="chrmrtns-header">
                <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
                <?php esc_html_e('Keyless Auth - Help & Instructions', 'keyless-auth'); ?>
            </h1>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Getting Started', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Keyless Auth allows your users to login without passwords using secure email magic links. Here\'s how to get started:', 'keyless-auth'); ?></p>

                <ol>
                    <li><strong><?php esc_html_e('Configure SMTP Settings:', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to SMTP tab and configure your email settings for reliable delivery.', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Customize Email Templates:', 'keyless-auth'); ?></strong> <?php esc_html_e('Use the Templates tab to customize how your login emails look.', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Add Login Form:', 'keyless-auth'); ?></strong> <?php esc_html_e('Use the shortcode [keyless-auth] on any page or post.', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Enable wp-login.php (Optional):', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options to add magic login to the WordPress login page.', 'keyless-auth'); ?></li>
                </ol>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Available Shortcodes', 'keyless-auth'); ?></h2>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Shortcode', 'keyless-auth'); ?></th>
                            <th><?php esc_html_e('Description', 'keyless-auth'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>[keyless-auth]</code></td>
                            <td><?php esc_html_e('Main passwordless login form', 'keyless-auth'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('How It Works', 'keyless-auth'); ?></h2>
                <ol>
                    <li><?php esc_html_e('User enters their email address or username', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('System generates a secure, time-limited token', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Email is sent with a magic login link', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('User clicks the link and is automatically logged in', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Token expires after 10 minutes for security', 'keyless-auth'); ?></li>
                </ol>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Security Features', 'keyless-auth'); ?></h2>
                <ul>
                    <li><strong><?php esc_html_e('Token Expiration:', 'keyless-auth'); ?></strong> <?php esc_html_e('All login links expire after 10 minutes', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('One-Time Use:', 'keyless-auth'); ?></strong> <?php esc_html_e('Each token can only be used once', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('IP Tracking:', 'keyless-auth'); ?></strong> <?php esc_html_e('Login attempts are logged with IP addresses', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Device Fingerprinting:', 'keyless-auth'); ?></strong> <?php esc_html_e('Tracks device information for audit purposes', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Database Logging:', 'keyless-auth'); ?></strong> <?php esc_html_e('All attempts are logged for security analysis', 'keyless-auth'); ?></li>
                </ul>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Troubleshooting', 'keyless-auth'); ?></h2>
                <dl>
                    <dt><strong><?php esc_html_e('Emails not being sent?', 'keyless-auth'); ?></strong></dt>
                    <dd><?php esc_html_e('Check your SMTP settings and test with the built-in email tester. Make sure your hosting provider allows email sending.', 'keyless-auth'); ?></dd>

                    <dt><strong><?php esc_html_e('Login links not working?', 'keyless-auth'); ?></strong></dt>
                    <dd><?php esc_html_e('Verify that tokens haven\'t expired (10 minute limit) and check that the link hasn\'t been used already.', 'keyless-auth'); ?></dd>

                    <dt><strong><?php esc_html_e('Users not receiving emails?', 'keyless-auth'); ?></strong></dt>
                    <dd><?php esc_html_e('Check spam folders and verify the user\'s email address is correct. Consider configuring DKIM/SPF records.', 'keyless-auth'); ?></dd>
                </dl>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Advanced Configuration', 'keyless-auth'); ?></h2>
                <h3><?php esc_html_e('Developer Functions', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('For developers, these functions are available:', 'keyless-auth'); ?></p>
                <ul>
                    <li><code>do_shortcode('[keyless-auth]')</code> - <?php esc_html_e('Display login form in templates', 'keyless-auth'); ?></li>
                </ul>

                <h3><?php esc_html_e('Database Tables', 'keyless-auth'); ?></h3>
                <p><?php esc_html_e('Keyless Auth creates these custom tables for optimal performance:', 'keyless-auth'); ?></p>
                <ul>
                    <li><code>wp_chrmrtns_kla_login_logs</code> - <?php esc_html_e('Login attempt tracking', 'keyless-auth'); ?></li>
                    <li><code>wp_chrmrtns_kla_mail_logs</code> - <?php esc_html_e('Email sending logs', 'keyless-auth'); ?></li>
                    <li><code>wp_chrmrtns_kla_login_tokens</code> - <?php esc_html_e('Secure token storage', 'keyless-auth'); ?></li>
                    <li><code>wp_chrmrtns_kla_user_devices</code> - <?php esc_html_e('Device fingerprinting', 'keyless-auth'); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Sanitize checkbox values
     */
    public function sanitize_checkbox($input) {
        return ($input === '1' || $input === 1 || $input === true) ? '1' : '0';
    }
}