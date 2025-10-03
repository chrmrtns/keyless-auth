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

        // Add AJAX handler for admin 2FA disable
        add_action('wp_ajax_chrmrtns_kla_admin_disable_2fa', array($this, 'ajax_admin_disable_2fa'));

        // Add AJAX handlers for emergency mode
        add_action('wp_ajax_chrmrtns_disable_emergency_mode', array($this, 'ajax_disable_emergency_mode'));
        add_action('wp_ajax_chrmrtns_dismiss_emergency_notice', array($this, 'ajax_dismiss_emergency_notice'));
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
            __('2FA User Management', 'keyless-auth'),
            __('2FA Users', 'keyless-auth'),
            'manage_options',
            'keyless-auth-2fa-users',
            array($this, 'tfa_users_page')
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
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_custom_login_url', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_redirect_wp_login', array(
            'sanitize_callback' => array($this, 'sanitize_checkbox'),
            'default' => '0'
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_custom_redirect_url', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
        ));
        register_setting('chrmrtns_kla_options_group', 'chrmrtns_kla_custom_2fa_setup_url', array(
            'sanitize_callback' => 'esc_url_raw',
            'default' => ''
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
        // Show emergency disable notice for both wp-config constant AND database option
        if ((defined('CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY') && CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY === true) ||
            get_option('chrmrtns_kla_2fa_emergency_disable', false)) {
            echo '<div class="notice notice-error" style="border-left-color: #dc3232; background: #fef7f7; padding: 15px; margin: 15px 0; box-shadow: 0 2px 5px rgba(220, 50, 50, 0.2);">';
            echo '<p style="font-size: 16px; margin: 0 0 10px 0;"><strong style="color: #dc3232;">🚨 2FA Emergency Mode Active</strong></p>';

            // Show different content based on how emergency mode was activated
            if (defined('CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY') && CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY === true) {
                echo '<p style="margin: 0 0 15px 0; color: #333;">' . esc_html__('Two-Factor Authentication system is disabled via wp-config.php constant for emergency access.', 'keyless-auth') . '</p>';
                echo '<div style="background: #fff; padding: 10px; border-radius: 5px; border-left: 3px solid #dc3232; margin-bottom: 10px;">';
                echo '<p style="margin: 0; color: #666;"><strong>wp-config.php constant:</strong> Remove <code>CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY</code> from wp-config.php to disable emergency mode.</p>';
                echo '</div>';
            } else {
                echo '<p style="margin: 0 0 15px 0; color: #333;">' . esc_html__('Two-Factor Authentication is currently disabled because no administrator has 2FA set up and the emergency option is enabled.', 'keyless-auth') . '</p>';
                echo '<div style="background: #fff; padding: 10px; border-radius: 5px; border-left: 3px solid #dc3232; margin-bottom: 10px;">';
                echo '<p style="margin: 0 0 8px 0; font-weight: bold; color: #dc3232;">' . esc_html__('Disable emergency mode once you have at least one administrator with 2FA properly configured.', 'keyless-auth') . '</p>';
                echo '<p style="margin: 0; color: #666;">' . esc_html__('Go to Keyless Auth → Options → Emergency Disable 2FA to turn this off after setting up 2FA.', 'keyless-auth') . '</p>';
                echo '</div>';
            }

            echo '<div style="background: #fff; padding: 10px; border-radius: 5px; border-left: 3px solid #dc3232;">';
            echo '<label style="display: flex; align-items: center; cursor: pointer;">';
            echo '<input type="checkbox" id="chrmrtns_emergency_dismiss" style="margin-right: 8px;" />';
            echo '<span>' . esc_html__('I understand this is temporary. Don\'t show this notice for 24 hours.', 'keyless-auth') . '</span>';
            echo '</label>';
            echo '</div>';
            echo '</div>';
        }

        // Show emergency admin bypass notice (when admin doesn't have 2FA but system requires it)
        $user_id = get_current_user_id();
        if (get_transient('chrmrtns_kla_emergency_admin_notice_' . $user_id)) {
            echo '<div class="notice notice-warning" style="border-left-color: #ffb900; background: #fffbf0; padding: 15px; margin: 15px 0; box-shadow: 0 2px 5px rgba(255, 185, 0, 0.2);">';
            echo '<p style="font-size: 16px; margin: 0 0 10px 0;"><strong style="color: #b26500;">⚠️ Admin 2FA Setup Needed</strong></p>';
            echo '<p style="margin: 0 0 8px 0; color: #333;">' . esc_html__('You\'re the only administrator and don\'t have 2FA set up yet. You have emergency access for now.', 'keyless-auth') . '</p>';
            echo '<p style="margin: 0 0 15px 0; color: #666;">' . esc_html__('Set up 2FA when convenient to secure your account.', 'keyless-auth') . '</p>';
            /* translators: %s: shortcode name in code tags */
            echo '<p style="margin: 0 0 15px 0;"><em style="color: #666;">' . sprintf(esc_html__('Use the shortcode %s to set up 2FA.', 'keyless-auth'), '<code style="background: #f1f1f1; padding: 2px 4px; border-radius: 3px;">[keyless-auth-2fa]</code>') . '</em></p>';
            echo '<div style="background: #fff; padding: 10px; border-radius: 5px; border-left: 3px solid #ffb900;">';
            echo '<label style="display: flex; align-items: center; cursor: pointer;">';
            echo '<input type="checkbox" id="chrmrtns_grace_dismiss" style="margin-right: 8px;" />';
            echo '<span>' . esc_html__('Remind me again tomorrow (24-hour grace period)', 'keyless-auth') . '</span>';
            echo '</label>';
            echo '</div>';
            echo '</div>';
        }

        // Add JavaScript for handling emergency notice dismissal
        if ((defined('CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY') && CHRMRTNS_KLA_DISABLE_2FA_EMERGENCY === true) ||
            get_option('chrmrtns_kla_2fa_emergency_disable', false) ||
            get_transient('chrmrtns_kla_emergency_admin_notice_' . get_current_user_id())) {
            ?>
            <script>
            jQuery(document).ready(function($) {
                $('#chrmrtns_disable_emergency').on('click', function() {
                    if (confirm('<?php echo esc_js(__('Are you sure you want to disable emergency mode? The 2FA system will be re-enabled.', 'keyless-auth')); ?>')) {
                        $.post(ajaxurl, {
                            action: 'chrmrtns_disable_emergency_mode',
                            nonce: '<?php echo esc_js(wp_create_nonce('chrmrtns_disable_emergency')); ?>'
                        }, function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('<?php echo esc_js(__('Error disabling emergency mode. Please try again.', 'keyless-auth')); ?>');
                            }
                        });
                    }
                });

                $('#chrmrtns_emergency_dismiss').on('change', function() {
                    if ($(this).is(':checked')) {
                        $.post(ajaxurl, {
                            action: 'chrmrtns_dismiss_emergency_notice',
                            nonce: '<?php echo esc_js(wp_create_nonce('chrmrtns_emergency_dismiss')); ?>',
                            type: 'emergency'
                        });
                        $(this).closest('.notice').fadeOut();
                    }
                });

                $('#chrmrtns_grace_dismiss').on('change', function() {
                    if ($(this).is(':checked')) {
                        $.post(ajaxurl, {
                            action: 'chrmrtns_dismiss_emergency_notice',
                            nonce: '<?php echo esc_js(wp_create_nonce('chrmrtns_emergency_dismiss')); ?>',
                            type: 'grace'
                        });
                        $(this).closest('.notice').fadeOut();
                    }
                });
            });
            </script>
            <?php
        }

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
            'keyless-auth_page_keyless-auth-2fa-users',
            'keyless-auth_page_keyless-auth-help'
        );
        
        if (in_array($hook, $allowed_pages)) {
            // Enqueue main admin stylesheet
            wp_enqueue_style('chrmrtns_kla_admin_stylesheet', CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/style-back-end.css', array(), CHRMRTNS_KLA_VERSION);
            
            // Enqueue additional admin styles
            wp_enqueue_style('chrmrtns_kla_admin_style', CHRMRTNS_KLA_PLUGIN_URL . 'assets/css/admin-style.css', array(), CHRMRTNS_KLA_VERSION);
            
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
            wp_enqueue_script('chrmrtns_kla_admin_script', CHRMRTNS_KLA_PLUGIN_URL . 'assets/js/admin-script.js', array('jquery'), CHRMRTNS_KLA_VERSION, true);
            
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

            $custom_login_url = isset($_POST['chrmrtns_kla_custom_login_url']) ? esc_url_raw(wp_unslash($_POST['chrmrtns_kla_custom_login_url'])) : '';
            update_option('chrmrtns_kla_custom_login_url', $custom_login_url);

            $redirect_wp_login = isset($_POST['chrmrtns_kla_redirect_wp_login']) ? '1' : '0';
            update_option('chrmrtns_kla_redirect_wp_login', $redirect_wp_login);

            $custom_redirect_url = isset($_POST['chrmrtns_kla_custom_redirect_url']) ? esc_url_raw(wp_unslash($_POST['chrmrtns_kla_custom_redirect_url'])) : '';
            update_option('chrmrtns_kla_custom_redirect_url', $custom_redirect_url);

            $custom_2fa_setup_url = isset($_POST['chrmrtns_kla_custom_2fa_setup_url']) ? esc_url_raw(wp_unslash($_POST['chrmrtns_kla_custom_2fa_setup_url'])) : '';
            update_option('chrmrtns_kla_custom_2fa_setup_url', $custom_2fa_setup_url);

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

            // Handle emergency mode setting
            $emergency_mode = isset($_POST['chrmrtns_kla_2fa_emergency_disable']) ? true : false;
            update_option('chrmrtns_kla_2fa_emergency_disable', $emergency_mode);

            if ($emergency_mode) {
                echo '<div class="notice notice-warning"><p>' . esc_html__('Emergency mode is now enabled. 2FA system is disabled for all users.', 'keyless-auth') . '</p></div>';
            } else {
                echo '<div class="notice notice-success"><p>' . esc_html__('Emergency mode is disabled. 2FA system is now active.', 'keyless-auth') . '</p></div>';
            }

            $lockout_duration = isset($_POST['chrmrtns_kla_2fa_lockout_duration']) ? intval($_POST['chrmrtns_kla_2fa_lockout_duration']) : 15;
            update_option('chrmrtns_kla_2fa_lockout_duration', $lockout_duration);

            add_settings_error('chrmrtns_kla_options', 'settings_updated', __('Options saved successfully!', 'keyless-auth'), 'updated');
        }

        settings_errors('chrmrtns_kla_options');

        $enable_wp_login = get_option('chrmrtns_kla_enable_wp_login', '0');
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
                                <?php esc_html_e('Add a magic login field to the WordPress login page (wp-login.php).', 'keyless-auth'); ?>
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
                                <?php esc_html_e('When enabled, all requests to wp-login.php will be redirected to your custom login page. Emergency bypass: add ?kla_use_wp_login=1 to access wp-login.php directly.', 'keyless-auth'); ?>
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

                <!-- 2FA Settings Section -->
                <h2 style="margin-top: 40px;"><?php esc_html_e('Two-Factor Authentication (2FA)', 'keyless-auth'); ?></h2>
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
                    <li><strong><?php esc_html_e('Enable Two-Factor Authentication (Optional):', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options to enable 2FA system and use [keyless-auth-2fa] shortcode for user setup.', 'keyless-auth'); ?></li>
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
                            <td><?php esc_html_e('Main passwordless login form (magic link only). Supports attributes: redirect, button_text, description, label', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[keyless-auth-full]</code></td>
                            <td><?php esc_html_e('Complete login form with both password and magic link options. Supports attributes: redirect, show_title, title_text', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><code>[keyless-auth-2fa]</code></td>
                            <td><?php esc_html_e('Two-factor authentication setup and management interface (requires 2FA system to be enabled in Options)', 'keyless-auth'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="chrmrtns_kla_card">
                <h2><?php esc_html_e('Shortcode Usage Examples', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Here are some examples of how to use the shortcodes:', 'keyless-auth'); ?></p>

                <h4><?php esc_html_e('Basic Usage:', 'keyless-auth'); ?></h4>
                <p><code>[keyless-auth]</code> - <?php esc_html_e('Magic link login form only', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-full]</code> - <?php esc_html_e('Both password and magic link options', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-2fa]</code> - <?php esc_html_e('2FA setup interface (when 2FA is enabled)', 'keyless-auth'); ?></p>

                <h4><?php esc_html_e('[keyless-auth] Options:', 'keyless-auth'); ?></h4>
                <p><code>[keyless-auth redirect="/dashboard/"]</code><br><?php esc_html_e('Redirect to dashboard after magic link login', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth button_text="Email login link"]</code><br><?php esc_html_e('Custom button text', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth label="Your Email"]</code><br><?php esc_html_e('Custom field label', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth description="Secure passwordless access"]</code><br><?php esc_html_e('Add description text above the form', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth button_text="Email login link" description="Secure passwordless access" label="Your Email" redirect="/dashboard/"]</code><br><?php esc_html_e('Combined options example', 'keyless-auth'); ?></p>

                <h4><?php esc_html_e('Advanced [keyless-auth-full] Options:', 'keyless-auth'); ?></h4>
                <p><code>[keyless-auth-full redirect="/dashboard/"]</code><br><?php esc_html_e('Redirect to dashboard after login', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-full show_title="no"]</code><br><?php esc_html_e('Hide the main title', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-full title_text="Member Login"]</code><br><?php esc_html_e('Custom title text', 'keyless-auth'); ?></p>
                <p><code>[keyless-auth-full title_text="Member Login" redirect="/members/" show_title="yes"]</code><br><?php esc_html_e('Combined options example', 'keyless-auth'); ?></p>
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
                <h2><?php esc_html_e('Two-Factor Authentication (2FA)', 'keyless-auth'); ?></h2>
                <p><?php esc_html_e('Add an extra layer of security with TOTP-based two-factor authentication using smartphone authenticator apps.', 'keyless-auth'); ?></p>

                <h3><?php esc_html_e('Setup Instructions', 'keyless-auth'); ?></h3>
                <ol>
                    <li><strong><?php esc_html_e('Enable 2FA System:', 'keyless-auth'); ?></strong> <?php esc_html_e('Go to Options → Enable 2FA System checkbox', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Configure Role Requirements:', 'keyless-auth'); ?></strong> <?php esc_html_e('Select user roles that require 2FA (optional)', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Add User Interface:', 'keyless-auth'); ?></strong> <?php esc_html_e('Place [keyless-auth-2fa] shortcode on a page for user setup', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('User Setup:', 'keyless-auth'); ?></strong> <?php esc_html_e('Users scan QR code with authenticator app and verify setup', 'keyless-auth'); ?></li>
                </ol>

                <h3><?php esc_html_e('Supported Authenticator Apps', 'keyless-auth'); ?></h3>
                <ul>
                    <li><strong>Google Authenticator</strong> (iOS/Android)</li>
                    <li><strong>Authy</strong> (iOS/Android/Desktop)</li>
                    <li><strong>1Password</strong> (Premium users)</li>
                    <li><strong>Microsoft Authenticator</strong> (iOS/Android)</li>
                    <li><?php esc_html_e('Any RFC 6238 compliant TOTP app', 'keyless-auth'); ?></li>
                </ul>

                <h3><?php esc_html_e('Key Features', 'keyless-auth'); ?></h3>
                <ul>
                    <li><strong><?php esc_html_e('Universal Coverage:', 'keyless-auth'); ?></strong> <?php esc_html_e('Works with ALL login methods (magic links, passwords, SSO)', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Backup Codes:', 'keyless-auth'); ?></strong> <?php esc_html_e('10 single-use recovery codes for emergency access', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Admin Controls:', 'keyless-auth'); ?></strong> <?php esc_html_e('Admins can disable 2FA for any user', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Grace Periods:', 'keyless-auth'); ?></strong> <?php esc_html_e('Configurable setup time for required users (1-30 days)', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Failed Attempt Protection:', 'keyless-auth'); ?></strong> <?php esc_html_e('Automatic lockouts after too many failed attempts', 'keyless-auth'); ?></li>
                </ul>

                <h3><?php esc_html_e('API and Programmatic Access', 'keyless-auth'); ?></h3>
                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Important:', 'keyless-auth'); ?></strong> <?php esc_html_e('REST API and XML-RPC requests bypass 2FA when using Application Passwords.', 'keyless-auth'); ?></p>
                </div>
                <h4><?php esc_html_e('Application Password Requirements', 'keyless-auth'); ?></h4>
                <p><?php esc_html_e('For programmatic access to WordPress, you MUST use Application Passwords:', 'keyless-auth'); ?></p>
                <ul>
                    <li><strong><?php esc_html_e('REST API:', 'keyless-auth'); ?></strong> <?php esc_html_e('All REST API requests must authenticate using Application Passwords', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('XML-RPC:', 'keyless-auth'); ?></strong> <?php esc_html_e('XML-RPC requests must use Application Passwords (not regular passwords)', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('WP-CLI:', 'keyless-auth'); ?></strong> <?php esc_html_e('Command-line tools automatically bypass 2FA', 'keyless-auth'); ?></li>
                    <li><strong><?php esc_html_e('Third-party Apps:', 'keyless-auth'); ?></strong> <?php esc_html_e('Mobile apps, CI/CD tools, integrations must use Application Passwords', 'keyless-auth'); ?></li>
                </ul>

                <h4><?php esc_html_e('How to Create Application Passwords', 'keyless-auth'); ?></h4>
                <ol>
                    <li><?php esc_html_e('Go to Users → Your Profile', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Scroll to "Application Passwords" section', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Enter a name for your application (e.g., "Mobile App", "API Script")', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Click "Add New Application Password"', 'keyless-auth'); ?></li>
                    <li><?php esc_html_e('Copy the generated password and use it for API authentication', 'keyless-auth'); ?></li>
                </ol>

                <h4><?php esc_html_e('Authentication Methods Overview', 'keyless-auth'); ?></h4>
                <table class="wp-list-table widefat fixed striped" style="margin: 15px 0;">
                    <thead>
                        <tr>
                            <th style="width: 25%;"><?php esc_html_e('Login Method', 'keyless-auth'); ?></th>
                            <th style="width: 20%;"><?php esc_html_e('2FA Required?', 'keyless-auth'); ?></th>
                            <th><?php esc_html_e('Notes', 'keyless-auth'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php esc_html_e('Interactive Login', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(Web browser, admin panel)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #d63638;"><?php esc_html_e('YES', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('All interactive logins require 2FA when enabled (magic links, passwords, SSO)', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('REST API', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(with Application Passwords)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #2271b1;"><?php esc_html_e('NO', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('Application Passwords provide separate secure authentication', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('XML-RPC', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(with Application Passwords)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #2271b1;"><?php esc_html_e('NO', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('Must use Application Passwords, not regular passwords', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('WP-CLI', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(Command line)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #2271b1;"><?php esc_html_e('NO', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('Automatically detected and bypassed', 'keyless-auth'); ?></td>
                        </tr>
                        <tr>
                            <td><strong><?php esc_html_e('Legacy API Access', 'keyless-auth'); ?></strong><br><small><?php esc_html_e('(using regular passwords)', 'keyless-auth'); ?></small></td>
                            <td><span style="color: #d63638;"><?php esc_html_e('BLOCKED', 'keyless-auth'); ?></span></td>
                            <td><?php esc_html_e('Will fail - must upgrade to Application Passwords', 'keyless-auth'); ?></td>
                        </tr>
                    </tbody>
                </table>

                <div class="notice notice-warning inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Security Note:', 'keyless-auth'); ?></strong> <?php esc_html_e('Application Passwords are time-limited tokens that can be revoked individually. They provide better security than using regular passwords for API access.', 'keyless-auth'); ?></p>
                </div>
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
                    <li><code>do_shortcode('[keyless-auth-2fa]')</code> - <?php esc_html_e('Display 2FA setup interface in templates', 'keyless-auth'); ?></li>
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
     * AJAX handler for admin 2FA disable
     */
    public function ajax_admin_disable_2fa() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'chrmrtns_kla_ajax_nonce')) {
            wp_send_json_error(__('Security check failed.', 'keyless-auth'));
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('You do not have sufficient permissions.', 'keyless-auth'));
        }

        // Get user ID
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        if (!$user_id) {
            wp_send_json_error(__('Invalid user ID.', 'keyless-auth'));
        }

        // Disable 2FA for user
        global $chrmrtns_kla_database;
        if (!$chrmrtns_kla_database) {
            wp_send_json_error(__('Database not available.', 'keyless-auth'));
        }

        $result = $chrmrtns_kla_database->disable_user_2fa($user_id);

        if ($result) {
            wp_send_json_success(__('2FA has been disabled for the user.', 'keyless-auth'));
        } else {
            wp_send_json_error(__('Failed to disable 2FA. Please try again.', 'keyless-auth'));
        }
    }

    /**
     * 2FA User Management page
     */
    public function tfa_users_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'keyless-auth'));
        }

        global $chrmrtns_kla_database;

        if (!$chrmrtns_kla_database) {
            wp_die(esc_html__('Database not available.', 'keyless-auth'));
        }

        // Handle search
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Reading GET parameter for search functionality, no form processing
        $search_query = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $users_with_2fa = $chrmrtns_kla_database->get_2fa_users($search_query);

        ?>
        <div class="wrap">
            <h1 class="chrmrtns-header">
                <img src="<?php echo esc_url(CHRMRTNS_KLA_PLUGIN_URL . 'assets/logo_150_150.png'); ?>" alt="<?php esc_attr_e('Keyless Auth Logo', 'keyless-auth'); ?>" class="chrmrtns-header-logo" />
                <?php esc_html_e('2FA User Management', 'keyless-auth'); ?>
            </h1>

            <div class="chrmrtns_kla_card">
                <p class="description"><?php esc_html_e('Manage 2FA settings for individual users. You can search for specific users and disable 2FA if needed.', 'keyless-auth'); ?></p>

                <!-- Search Form -->
                <form method="get" style="margin: 20px 0;">
                    <input type="hidden" name="page" value="keyless-auth-2fa-users" />
                    <p class="search-box">
                        <label class="screen-reader-text" for="user-search-input"><?php esc_html_e('Search Users:', 'keyless-auth'); ?></label>
                        <input type="search" id="user-search-input" name="search" value="<?php echo esc_attr($search_query); ?>" placeholder="<?php esc_attr_e('Search by username or email...', 'keyless-auth'); ?>" />
                        <?php submit_button(__('Search Users', 'keyless-auth'), 'secondary', 'search_submit', false); ?>
                        <?php if (!empty($search_query)): ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=keyless-auth-2fa-users')); ?>" class="button"><?php esc_html_e('Clear', 'keyless-auth'); ?></a>
                        <?php endif; ?>
                    </p>
                </form>

                <?php if (empty($users_with_2fa)): ?>
                    <?php if (!empty($search_query)): ?>
                        <p><em><?php
                        /* translators: %s: search query term */
                        printf(esc_html__('No users found matching "%s".', 'keyless-auth'), esc_html($search_query)); ?></em></p>
                        <p><a href="<?php echo esc_url(admin_url('admin.php?page=keyless-auth-2fa-users')); ?>"><?php esc_html_e('Show all 2FA users', 'keyless-auth'); ?></a></p>
                    <?php else: ?>
                        <p><em><?php esc_html_e('No users with 2FA enabled yet. Users can set up 2FA using the [keyless-auth-2fa] shortcode.', 'keyless-auth'); ?></em></p>
                    <?php endif; ?>
                <?php else: ?>
                    <?php if (!empty($search_query)): ?>
                        <p><strong><?php
                        /* translators: 1: search query, 2: number of users found */
                        printf(esc_html__('Search results for "%1$s" (%2$d users found):', 'keyless-auth'), esc_html($search_query), count($users_with_2fa)); ?></strong></p>
                    <?php else: ?>
                        <p><strong><?php
                        /* translators: %d: total number of users with 2FA enabled */
                        printf(esc_html__('Total users with 2FA: %d', 'keyless-auth'), count($users_with_2fa)); ?></strong></p>
                    <?php endif; ?>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col"><?php esc_html_e('User', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Email', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Role', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('2FA Status', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Last Used', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Failed Attempts', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Backup Codes', 'keyless-auth'); ?></th>
                                <th scope="col"><?php esc_html_e('Actions', 'keyless-auth'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users_with_2fa as $user): ?>
                                <?php $wp_user = get_user_by('ID', $user->ID); ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($user->user_login); ?></strong>
                                        <br><small><?php echo esc_html($user->display_name); ?></small>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td>
                                        <?php
                                        if ($wp_user) {
                                            $roles = $wp_user->roles;
                                            echo esc_html(ucfirst(implode(', ', $roles)));
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user->totp_enabled): ?>
                                            <span style="color: #46b450;">✓ <?php esc_html_e('Enabled', 'keyless-auth'); ?></span>
                                            <?php if ($user->totp_locked_until && strtotime($user->totp_locked_until) > time()): ?>
                                                <br><span style="color: #dc3232;">🔒 <?php esc_html_e('Locked', 'keyless-auth'); ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span style="color: #666;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($user->totp_last_used) {
                                            echo esc_html(wp_date(get_option('date_format') . ' ' . get_option('time_format'), strtotime($user->totp_last_used)));
                                        } else {
                                            echo '<em>' . esc_html__('Never', 'keyless-auth') . '</em>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($user->totp_enabled) {
                                            $attempts = intval($user->totp_failed_attempts);
                                            if ($attempts > 0) {
                                                echo '<span style="color: #dc3232;">' . esc_html($attempts) . '</span>';
                                            } else {
                                                echo '0';
                                            }
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($user->totp_enabled) {
                                            // Get backup codes from the user settings
                                            $user_settings = $chrmrtns_kla_database->get_user_2fa_settings($user->ID);
                                            if ($user_settings && !empty($user_settings->totp_backup_codes)) {
                                                $backup_codes = $user_settings->totp_backup_codes;
                                                $total_codes = count($backup_codes);
                                                $remaining = $total_codes; // For now, assume all are available (we don't track usage individually)

                                                if ($remaining === 0) {
                                                    echo '<span style="color: #dc3232;">' . esc_html($remaining) . '/' . esc_html($total_codes) . '</span>';
                                                } elseif ($remaining < 3) {
                                                    echo '<span style="color: #ffb900;">' . esc_html($remaining) . '/' . esc_html($total_codes) . '</span>';
                                                } else {
                                                    echo '<span style="color: #46b450;">' . esc_html($remaining) . '/' . esc_html($total_codes) . '</span>';
                                                }
                                            } else {
                                                echo '<em>' . esc_html__('None', 'keyless-auth') . '</em>';
                                            }
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($user->totp_enabled): ?>
                                            <button type="button" class="button button-secondary button-small" onclick="chrmrtnsDisable2FA(<?php echo intval($user->ID); ?>, '<?php echo esc_js($user->user_login); ?>')">
                                                <?php esc_html_e('Disable 2FA', 'keyless-auth'); ?>
                                            </button>
                                            <?php if ($user->totp_locked_until && strtotime($user->totp_locked_until) > time()): ?>
                                                <br><button type="button" class="button button-secondary button-small" style="margin-top: 5px;" onclick="chrmrtnsUnlock2FA(<?php echo intval($user->ID); ?>, '<?php echo esc_js($user->user_login); ?>')">
                                                    <?php esc_html_e('Unlock', 'keyless-auth'); ?>
                                                </button>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <script>
        function chrmrtnsDisable2FA(userId, username) {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to disable 2FA for', 'keyless-auth')); ?> "' + username + '"?\n\n<?php echo esc_js(__('This action will:', 'keyless-auth')); ?>\n- <?php echo esc_js(__('Remove their TOTP secret', 'keyless-auth')); ?>\n- <?php echo esc_js(__('Delete all backup codes', 'keyless-auth')); ?>\n- <?php echo esc_js(__('Clear any lockouts', 'keyless-auth')); ?>')) {
                return;
            }

            var data = {
                'action': 'chrmrtns_kla_admin_disable_2fa',
                'user_id': userId,
                'nonce': '<?php echo esc_js(wp_create_nonce('chrmrtns_kla_ajax_nonce')); ?>'
            };

            jQuery.post(ajaxurl, data, function(response) {
                if (response.success) {
                    alert(response.data);
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            });
        }

        function chrmrtnsUnlock2FA(userId, username) {
            if (!confirm('<?php echo esc_js(__('Are you sure you want to unlock 2FA for', 'keyless-auth')); ?> "' + username + '"?')) {
                return;
            }

            // You could add an unlock AJAX handler here if needed
            alert('<?php echo esc_js(__('Unlock functionality can be added if needed.', 'keyless-auth')); ?>');
        }
        </script>
        <?php
    }

    /**
     * Get the login URL (custom or default)
     *
     * @return string Login URL
     */
    public static function get_login_url() {
        $custom_login_url = get_option('chrmrtns_kla_custom_login_url', '');
        if (!empty($custom_login_url)) {
            return esc_url($custom_login_url);
        }
        return wp_login_url();
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

    /**
     * Sanitize checkbox values
     */
    public function sanitize_checkbox($input) {
        return ($input === '1' || $input === 1 || $input === true) ? '1' : '0';
    }
}