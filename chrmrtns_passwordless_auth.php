<?php
/**
* Plugin Name: Passwordless Auth
* Plugin URI: https://github.com/chrmrtns/passwordless-auth
* Description: Enhanced passwordless authentication with improved security. Fork of Passwordless Login by Cozmoslabs with additional security features.
* Version: 2.0.2
* Author: Chris Martens
* Author URI: https://github.com/chrmrtns
* License: GPL2
* Text Domain: chrmrtns-passwordless-auth
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
define('CHRMRTNS_PASSWORDLESS_VERSION', '2.0.2');
define('CHRMRTNS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHRMRTNS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CHRMRTNS_PLUGIN_FILE', __FILE__);

/**
 * Main plugin class
 */
class Chrmrtns_Passwordless_Auth {
    
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
        add_action('init', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        
        // Include existing notices class
        if (file_exists(CHRMRTNS_PLUGIN_DIR . 'inc/chrmrtns.class.notices.php')) {
            include_once CHRMRTNS_PLUGIN_DIR . 'inc/chrmrtns.class.notices.php';
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
            'class-chrmrtns-core.php',
            'class-chrmrtns-admin.php',
            'class-chrmrtns-smtp.php',
            'class-chrmrtns-mail-logger.php',
            'class-chrmrtns-email-templates.php'
        );
        
        foreach ($classes as $class_file) {
            $file_path = CHRMRTNS_PLUGIN_DIR . 'includes/' . $class_file;
            if (file_exists($file_path)) {
                require_once $file_path;
            }
        }
    }
    
    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize core functionality
        if (class_exists('Chrmrtns_Core')) {
            new Chrmrtns_Core();
        }
        
        // Initialize admin functionality (only in admin)
        if (is_admin() && class_exists('Chrmrtns_Admin')) {
            new Chrmrtns_Admin();
        }
        
        // Initialize SMTP functionality
        if (class_exists('Chrmrtns_SMTP')) {
            new Chrmrtns_SMTP();
        }
        
        // Initialize mail logging
        if (class_exists('Chrmrtns_Mail_Logger')) {
            new Chrmrtns_Mail_Logger();
        }
    }
    
    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'chrmrtns-passwordless-auth',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }
}

/**
 * Initialize plugin
 */
function chrmrtns_passwordless_auth_init() {
    return Chrmrtns_Passwordless_Auth::get_instance();
}

// Start the plugin
chrmrtns_passwordless_auth_init();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'chrmrtns_activation_hook');
function chrmrtns_activation_hook() {
    // Set default options
    add_option('chrmrtns_email_template', 'default');
    add_option('chrmrtns_button_color', '#007bff');
    add_option('chrmrtns_button_hover_color', '#0056b3');
    add_option('chrmrtns_link_color', '#007bff');
    add_option('chrmrtns_link_hover_color', '#0056b3');
    add_option('chrmrtns_mail_logging_enabled', '0');
    add_option('chrmrtns_mail_log_size_limit', '100');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Deactivation hook
 */
register_deactivation_hook(__FILE__, 'chrmrtns_deactivation_hook');
function chrmrtns_deactivation_hook() {
    // Clean up temporary data
    global $wpdb;
    
    // Remove all login tokens
    $wpdb->delete(
        $wpdb->usermeta,
        array(
            'meta_key' => 'chrmrtns_login_token'
        )
    );
    
    $wpdb->delete(
        $wpdb->usermeta,
        array(
            'meta_key' => 'chrmrtns_login_token_expiration'
        )
    );
    
    // Remove temporary options
    delete_option('chrmrtns_login_request_error');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * Uninstall hook
 */
register_uninstall_hook(__FILE__, 'chrmrtns_uninstall_hook');
function chrmrtns_uninstall_hook() {
    // Remove all plugin options
    delete_option('chrmrtns_email_template');
    delete_option('chrmrtns_custom_email_body');
    delete_option('chrmrtns_button_color');
    delete_option('chrmrtns_button_hover_color');
    delete_option('chrmrtns_link_color');
    delete_option('chrmrtns_link_hover_color');
    delete_option('chrmrtns_smtp_settings');
    delete_option('chrmrtns_mail_logging_enabled');
    delete_option('chrmrtns_mail_log_size_limit');
    delete_option('chrmrtns_learn_more_dismiss_notification');
    
    // Remove all mail logs
    $args = array(
        'post_type' => 'chrmrtns_mail_logs',
        'posts_per_page' => -1,
        'post_status' => 'any'
    );
    $logs = get_posts($args);
    foreach ($logs as $log) {
        wp_delete_post($log->ID, true);
    }
    
    // Remove user meta
    global $wpdb;
    $wpdb->delete(
        $wpdb->usermeta,
        array(
            'meta_key' => 'chrmrtns_login_token'
        )
    );
    
    $wpdb->delete(
        $wpdb->usermeta,
        array(
            'meta_key' => 'chrmrtns_login_token_expiration'
        )
    );
}