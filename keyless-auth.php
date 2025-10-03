<?php
/**
* Plugin Name: Keyless Auth - Login without Passwords
* Plugin URI: https://github.com/chrmrtns/keyless-auth
* Description: Enhanced passwordless authentication allowing users to login securely without passwords via email magic links. Fork of Passwordless Login by Cozmoslabs with additional security features.
* Version: 2.6.1
* Author: Chris Martens
* Author URI: https://github.com/chrmrtns
* License: GPL2
* Text Domain: keyless-auth
* Domain Path: /languages
*/
/* 
Original Copyright: Cozmoslabs.com
Fork Copyright: Chris Martens

Based on Passwordless Login by Cozmoslabs, sareiodata
Enhanced with additional security features and improvements

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CHRMRTNS_KLA_VERSION', '2.6.1');
define('CHRMRTNS_KLA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHRMRTNS_KLA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CHRMRTNS_KLA_PLUGIN_FILE', __FILE__);


/**
 * Main plugin class
 */
class Chrmrtns_KLA_Main {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('plugins_loaded', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(CHRMRTNS_KLA_PLUGIN_FILE), array($this, 'add_plugin_action_links'));
        
        // Include existing notices class
        if (file_exists(CHRMRTNS_KLA_PLUGIN_DIR . 'inc/chrmrtns.class.notices.php')) {
            include_once CHRMRTNS_KLA_PLUGIN_DIR . 'inc/chrmrtns.class.notices.php';
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Include all class files
        $this->include_classes();
        
        // Initialize components
        $this->init_components();
    }
    
    /**
     * Include all class files
     */
    private function include_classes() {
        $classes = array(
            'class-chrmrtns-kla-database.php',
            'class-chrmrtns-kla-core.php',
            'class-chrmrtns-kla-admin.php',
            'class-chrmrtns-kla-smtp.php',
            'class-chrmrtns-kla-mail-logger.php',
            'class-chrmrtns-kla-email-templates.php',
            'class-chrmrtns-kla-totp.php',
            'class-chrmrtns-kla-2fa-core.php',
            'class-chrmrtns-kla-2fa-frontend.php'
        );
        
        foreach ($classes as $class_file) {
            $file_path = CHRMRTNS_KLA_PLUGIN_DIR . 'includes/' . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize database functionality
        global $chrmrtns_kla_database;
        if (class_exists('Chrmrtns_KLA_Database')) {
            $chrmrtns_kla_database = new Chrmrtns_KLA_Database();
        }

        // Initialize core functionality
        if (class_exists('Chrmrtns_KLA_Core')) {
            new Chrmrtns_KLA_Core();
        }

        // Initialize admin functionality (only in admin)
        if (is_admin() && class_exists('Chrmrtns_KLA_Admin')) {
            new Chrmrtns_KLA_Admin();
        }

        // Initialize SMTP functionality
        if (class_exists('Chrmrtns_KLA_SMTP')) {
            new Chrmrtns_KLA_SMTP();
        }

        // Initialize mail logging
        if (class_exists('Chrmrtns_KLA_Mail_Logger')) {
            new Chrmrtns_KLA_Mail_Logger();
        }

        // Initialize 2FA functionality (singleton to prevent multiple instances)
        if (class_exists('Chrmrtns_KLA_2FA_Core')) {
            global $chrmrtns_kla_2fa_core;
            $chrmrtns_kla_2fa_core = Chrmrtns_KLA_2FA_Core::get_instance();
        }

        // Initialize 2FA frontend
        if (class_exists('Chrmrtns_KLA_2FA_Frontend')) {
            new Chrmrtns_KLA_2FA_Frontend();
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'keyless-auth',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . esc_url(admin_url('admin.php?page=keyless-auth')) . '">' . esc_html__('Settings', 'keyless-auth') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

/**
 * Initialize plugin
 */
function chrmrtns_kla_init() {
    return Chrmrtns_KLA_Main::get_instance();
}

// Start the plugin
chrmrtns_kla_init();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'chrmrtns_kla_activation_hook');
function chrmrtns_kla_activation_hook() {
    // Include database class
    require_once plugin_dir_path(__FILE__) . 'includes/class-chrmrtns-kla-database.php';

    // Create database tables
    $database = new Chrmrtns_KLA_Database();
    $database->create_tables();

    // Set default options
    add_option('chrmrtns_kla_email_template', 'default');
    add_option('chrmrtns_kla_button_color', '#007bff');
    add_option('chrmrtns_kla_button_hover_color', '#0056b3');
    add_option('chrmrtns_kla_link_color', '#007bff');
    add_option('chrmrtns_kla_link_hover_color', '#0056b3');
    add_option('chrmrtns_kla_button_text_color', '#ffffff');
    add_option('chrmrtns_kla_button_hover_text_color', '#ffffff');
    add_option('chrmrtns_kla_link_hover_color', '#0056b3');
    add_option('chrmrtns_kla_mail_logging_enabled', '1');
    add_option('chrmrtns_kla_mail_log_size_limit', '100');
    add_option('chrmrtns_kla_successful_logins', 0);

    // Set default 2FA options (disabled by default)
    add_option('chrmrtns_kla_2fa_enabled', false);
    add_option('chrmrtns_kla_2fa_required_roles', array());
    add_option('chrmrtns_kla_2fa_grace_period', 10);
    add_option('chrmrtns_kla_2fa_grace_message', __('Your account requires 2FA setup within {days} days for security.', 'keyless-auth'));
    add_option('chrmrtns_kla_2fa_max_attempts', 5);
    add_option('chrmrtns_kla_2fa_lockout_duration', 15);

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'chrmrtns_kla_deactivation_hook');
function chrmrtns_kla_deactivation_hook() {
    // Include database class
    require_once plugin_dir_path(__FILE__) . 'includes/class-chrmrtns-kla-database.php';

    // Clean up expired tokens from database
    $database = new Chrmrtns_KLA_Database();
    $database->cleanup_expired_tokens();

    // Clean up legacy user meta tokens
    $users_with_tokens = get_users(array(
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Acceptable for one-time deactivation cleanup
        'meta_key' => 'chrmrtns_kla_login_token',
        'fields' => 'ID'
    ));

    foreach ($users_with_tokens as $user_id) {
        delete_user_meta($user_id, 'chrmrtns_kla_login_token');
        delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');
    }

    // Remove temporary options
    delete_option('chrmrtns_kla_login_request_error');

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Uninstall hook
 */
register_uninstall_hook(__FILE__, 'chrmrtns_kla_uninstall_hook');
function chrmrtns_kla_uninstall_hook() {
    // Include database class
    require_once plugin_dir_path(__FILE__) . 'includes/class-chrmrtns-kla-database.php';

    // Drop all custom database tables
    $database = new Chrmrtns_KLA_Database();
    $database->drop_tables();

    // Remove all plugin options
    delete_option('chrmrtns_kla_email_template');
    delete_option('chrmrtns_kla_custom_email_body');
    delete_option('chrmrtns_kla_custom_email_styles');
    delete_option('chrmrtns_kla_button_color');
    delete_option('chrmrtns_kla_button_hover_color');
    delete_option('chrmrtns_kla_button_text_color');
    delete_option('chrmrtns_kla_button_hover_text_color');
    delete_option('chrmrtns_kla_link_color');
    delete_option('chrmrtns_kla_link_hover_color');
    delete_option('chrmrtns_kla_smtp_settings');
    delete_option('chrmrtns_kla_mail_logging_enabled');
    delete_option('chrmrtns_kla_mail_log_size_limit');
    delete_option('chrmrtns_kla_successful_logins');
    delete_option('chrmrtns_kla_login_request_error');
    delete_option('chrmrtns_kla_learn_more_dismiss_notification');
    delete_option('chrmrtns_kla_db_version');

    // Remove 2FA options
    delete_option('chrmrtns_kla_2fa_enabled');
    delete_option('chrmrtns_kla_2fa_required_roles');
    delete_option('chrmrtns_kla_2fa_grace_period');
    delete_option('chrmrtns_kla_2fa_grace_message');
    delete_option('chrmrtns_kla_2fa_max_attempts');
    delete_option('chrmrtns_kla_2fa_lockout_duration');
    
    // Remove all mail logs
    $args = array(
        'post_type' => 'chrmrtns_kla_logs',
        'posts_per_page' => -1,
        'post_status' => 'any'
    );
    $logs = get_posts($args);
    foreach ($logs as $log) {
        wp_delete_post($log->ID, true);
    }
    
    // Remove user meta using WordPress functions
    $users_with_tokens = get_users(array(
        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Acceptable for one-time uninstall cleanup
        'meta_key' => 'chrmrtns_kla_login_token',
        'fields' => 'ID'
    ));
    
    foreach ($users_with_tokens as $user_id) {
        delete_user_meta($user_id, 'chrmrtns_kla_login_token');
        delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');
    }
}