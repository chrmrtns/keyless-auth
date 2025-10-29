<?php
/**
* Plugin Name: Keyless Auth - Login without Passwords
* Plugin URI: https://github.com/chrmrtns/keyless-auth
* Description: Enhanced passwordless authentication with magic email links, two-factor authentication, SMTP integration, WooCommerce integration, and comprehensive security features for WordPress.
* Version: 3.2.1
* Author: Chris Martens
* Author URI: https://github.com/chrmrtns
* License: GPL2
* Text Domain: keyless-auth
* Domain Path: /languages
*/
/*
Copyright: Chris Martens

Originally based on Passwordless Login by Cozmoslabs, sareiodata
Extensively rewritten and enhanced with PSR-4 architecture, two-factor authentication,
SMTP integration, custom database tables, and comprehensive security features.

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
define('CHRMRTNS_KLA_VERSION', '3.2.1');
define('CHRMRTNS_KLA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CHRMRTNS_KLA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CHRMRTNS_KLA_PLUGIN_FILE', __FILE__);

// Load PSR-4 autoloader
require_once CHRMRTNS_KLA_PLUGIN_DIR . 'autoload.php';

// Use namespaced classes
use Chrmrtns\KeylessAuth\Core\Main;
use Chrmrtns\KeylessAuth\Core\Database;

/**
 * Initialize plugin
 */
function chrmrtns_kla_init() {
    return Main::get_instance();
}

// Start the plugin
chrmrtns_kla_init();

/**
 * Activation hook
 */
register_activation_hook(__FILE__, 'chrmrtns_kla_activation_hook');
function chrmrtns_kla_activation_hook() {
    // Create database tables
    $database = new Database();
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
    // Clean up expired tokens from database
    $database = new Database();
    $database->cleanup_expired_tokens();

    // Clean up legacy user meta tokens
    $users_with_tokens = get_users(array(
        'meta_key' => 'chrmrtns_kla_login_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Only runs on deactivation
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
    // Drop all custom database tables
    $database = new Database();
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
        'meta_key' => 'chrmrtns_kla_login_token', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Only runs on uninstall
        'fields' => 'ID'
    ));

    foreach ($users_with_tokens as $user_id) {
        delete_user_meta($user_id, 'chrmrtns_kla_login_token');
        delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');
    }
}
