#!/bin/bash

# Keyless Auth PSR-4 Refactoring Script
# Transforms old class files to namespaced versions
# Run from plugin root directory

set -e  # Exit on error

PLUGIN_DIR="/Users/christianmartens/Documents/GitHub/keyless-auth"
cd "$PLUGIN_DIR"

echo "🚀 Starting PSR-4 Refactoring..."

# Function to refactor a file
refactor_file() {
    local source_file="$1"
    local target_file="$2"
    local namespace="$3"
    local old_class_name="$4"
    local new_class_name="$5"

    echo "📝 Processing: $source_file -> $target_file"

    # Read the source file
    content=$(cat "$source_file")

    # Insert namespace after the opening PHP tag and security check
    # Find the line after "if (!defined('ABSPATH'))"
    content=$(echo "$content" | awk -v ns="$namespace" '
    /^\/\/ Exit if accessed directly/ {
        print
        getline
        print
        getline
        print
        getline
        print
        print ""
        print "namespace " ns ";"
        print ""
        next
    }
    {print}
    ')

    # Replace class declaration
    content=$(echo "$content" | sed "s/class $old_class_name/class $new_class_name/g")

    # Save to target file
    echo "$content" > "$target_file"

    echo "✅ Created: $target_file"
}

# Function to update class references in a file
update_references() {
    local file="$1"

    echo "🔄 Updating references in: $file"

    # Update new instantiations
    sed -i '' 's/new Chrmrtns_KLA_Database(/new Database(/g' "$file"
    sed -i '' 's/new Chrmrtns_KLA_TOTP(/new TOTP(/g' "$file"
    sed -i '' 's/new Chrmrtns_KLA_2FA_Core(/new Core(/g' "$file"
    sed -i '' 's/new Chrmrtns_KLA_2FA_Frontend(/new Frontend(/g' "$file"
    sed -i '' 's/new Chrmrtns_KLA_Email_Templates(/new Templates(/g' "$file"
    sed -i '' 's/new Chrmrtns_KLA_SMTP(/new SMTP(/g' "$file"
    sed -i '' 's/new Chrmrtns_KLA_Mail_Logger(/new MailLogger(/g' "$file"
    sed -i '' 's/new Chrmrtns_KLA_Core(/new Core(/g' "$file"
    sed -i '' 's/new Chrmrtns_KLA_Admin(/new Admin(/g' "$file"

    # Update static calls
    sed -i '' 's/Chrmrtns_KLA_2FA_Core::get_instance()/Core::get_instance()/g' "$file"
    sed -i '' 's/Chrmrtns_KLA_Admin::get_redirect_url/Admin::get_redirect_url/g' "$file"
    sed -i '' 's/Chrmrtns_KLA_Admin::get_login_url/Admin::get_login_url/g' "$file"

    # Update class_exists checks with escaped backslashes
    sed -i '' "s/class_exists('Chrmrtns_KLA_Database')/class_exists('Chrmrtns\\\\\\\\KeylessAuth\\\\\\\\Core\\\\\\\\Database')/g" "$file"
    sed -i '' "s/class_exists('Chrmrtns_KLA_Email_Templates')/class_exists('Chrmrtns\\\\\\\\KeylessAuth\\\\\\\\Email\\\\\\\\Templates')/g" "$file"
    sed -i '' "s/class_exists('Chrmrtns_KLA_2FA_Core')/class_exists('Chrmrtns\\\\\\\\KeylessAuth\\\\\\\\Security\\\\\\\\TwoFA\\\\\\\\Core')/g" "$file"
    sed -i '' "s/class_exists('Chrmrtns_KLA_Admin')/class_exists('Chrmrtns\\\\\\\\KeylessAuth\\\\\\\\Admin\\\\\\\\Admin')/g" "$file"

    echo "✅ Updated references in: $file"
}

# Function to add use statements
add_use_statements() {
    local file="$1"
    shift
    local use_statements=("$@")

    if [ ${#use_statements[@]} -eq 0 ]; then
        return
    fi

    echo "📦 Adding use statements to: $file"

    # Create use block
    use_block=""
    for use_stmt in "${use_statements[@]}"; do
        use_block="${use_block}use ${use_stmt};\n"
    done

    # Insert after namespace declaration
    awk -v use_block="$use_block" '
    /^namespace / {
        print
        print ""
        printf use_block
        next
    }
    {print}
    ' "$file" > "${file}.tmp" && mv "${file}.tmp" "$file"

    echo "✅ Added use statements"
}

echo ""
echo "📁 Refactoring class files..."
echo ""

# 1. Frontend (already done, but let's automate it)
# Skipping already completed files: Database, TOTP, Core (2FA)

# 2. TwoFA Frontend
if [ ! -f "includes/Security/TwoFA/Frontend.php" ]; then
    refactor_file \
        "includes/class-chrmrtns-kla-2fa-frontend.php" \
        "includes/Security/TwoFA/Frontend.php" \
        "Chrmrtns\\KeylessAuth\\Security\\TwoFA" \
        "Chrmrtns_KLA_2FA_Frontend" \
        "Frontend"

    update_references "includes/Security/TwoFA/Frontend.php"
    add_use_statements "includes/Security/TwoFA/Frontend.php"
fi

# 3. Email Templates
if [ ! -f "includes/Email/Templates.php" ]; then
    refactor_file \
        "includes/class-chrmrtns-kla-email-templates.php" \
        "includes/Email/Templates.php" \
        "Chrmrtns\\KeylessAuth\\Email" \
        "Chrmrtns_KLA_Email_Templates" \
        "Templates"

    update_references "includes/Email/Templates.php"
fi

# 4. Email SMTP
if [ ! -f "includes/Email/SMTP.php" ]; then
    refactor_file \
        "includes/class-chrmrtns-kla-smtp.php" \
        "includes/Email/SMTP.php" \
        "Chrmrtns\\KeylessAuth\\Email" \
        "Chrmrtns_KLA_SMTP" \
        "SMTP"

    update_references "includes/Email/SMTP.php"
fi

# 5. Email MailLogger
if [ ! -f "includes/Email/MailLogger.php" ]; then
    refactor_file \
        "includes/class-chrmrtns-kla-mail-logger.php" \
        "includes/Email/MailLogger.php" \
        "Chrmrtns\\KeylessAuth\\Email" \
        "Chrmrtns_KLA_Mail_Logger" \
        "MailLogger"

    update_references "includes/Email/MailLogger.php"
fi

# 6. Core/Core
if [ ! -f "includes/Core/Core.php" ]; then
    refactor_file \
        "includes/class-chrmrtns-kla-core.php" \
        "includes/Core/Core.php" \
        "Chrmrtns\\KeylessAuth\\Core" \
        "Chrmrtns_KLA_Core" \
        "Core"

    update_references "includes/Core/Core.php"
    add_use_statements "includes/Core/Core.php" \
        "Chrmrtns\\KeylessAuth\\Security\\TwoFA\\Core as TwoFACore" \
        "Chrmrtns\\KeylessAuth\\Email\\Templates" \
        "Chrmrtns\\KeylessAuth\\Admin\\Admin"
fi

# 7. Admin (LARGE FILE)
if [ ! -f "includes/Admin/Admin.php" ]; then
    refactor_file \
        "includes/class-chrmrtns-kla-admin.php" \
        "includes/Admin/Admin.php" \
        "Chrmrtns\\KeylessAuth\\Admin" \
        "Chrmrtns_KLA_Admin" \
        "Admin"

    update_references "includes/Admin/Admin.php"
    add_use_statements "includes/Admin/Admin.php" \
        "Chrmrtns\\KeylessAuth\\Core\\Database" \
        "Chrmrtns\\KeylessAuth\\Security\\TwoFA\\Core as TwoFACore" \
        "Chrmrtns\\KeylessAuth\\Security\\TwoFA\\TOTP" \
        "Chrmrtns\\KeylessAuth\\Email\\Templates" \
        "Chrmrtns\\KeylessAuth\\Email\\SMTP" \
        "Chrmrtns\\KeylessAuth\\Email\\MailLogger"
fi

echo ""
echo "🔄 Post-processing: Updating cross-references..."
echo ""

# Update all newly created files to reference each other correctly
for file in includes/Security/TwoFA/Frontend.php \
            includes/Email/Templates.php \
            includes/Email/SMTP.php \
            includes/Email/MailLogger.php \
            includes/Core/Core.php \
            includes/Admin/Admin.php; do
    if [ -f "$file" ]; then
        update_references "$file"
    fi
done

echo ""
echo "🎨 Creating Main.php (Bootstrap class)..."
echo ""

# 8. Extract Main class from keyless-auth.php and create Core/Main.php
cat > includes/Core/Main.php << 'MAINPHP'
<?php
/**
 * Main plugin bootstrap class
 *
 * @package Keyless Auth
 * @since 3.0.0
 */

namespace Chrmrtns\KeylessAuth\Core;

use Chrmrtns\KeylessAuth\Admin\Admin;
use Chrmrtns\KeylessAuth\Email\SMTP;
use Chrmrtns\KeylessAuth\Email\MailLogger;
use Chrmrtns\KeylessAuth\Security\TwoFA\Core as TwoFACore;
use Chrmrtns\KeylessAuth\Security\TwoFA\Frontend as TwoFAFrontend;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Main {

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
        // Initialize components
        $this->init_components();
    }

    /**
     * Initialize components
     */
    private function init_components() {
        // Initialize database functionality
        global $chrmrtns_kla_database;
        $chrmrtns_kla_database = new Database();

        // Initialize core functionality
        new Core();

        // Initialize admin functionality (only in admin)
        if (is_admin()) {
            new Admin();
        }

        // Initialize SMTP functionality
        new SMTP();

        // Initialize mail logging
        new MailLogger();

        // Initialize 2FA functionality (singleton to prevent multiple instances)
        global $chrmrtns_kla_2fa_core;
        $chrmrtns_kla_2fa_core = TwoFACore::get_instance();

        // Initialize 2FA frontend
        new TwoFAFrontend();
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'keyless-auth',
            false,
            dirname(plugin_basename(CHRMRTNS_KLA_PLUGIN_FILE)) . '/languages'
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
MAINPHP

echo "✅ Created: includes/Core/Main.php"

echo ""
echo "📝 Updating main plugin file..."
echo ""

# 9. Update keyless-auth.php
cat > keyless-auth.php << 'MAINFILE'
<?php
/**
* Plugin Name: Keyless Auth - Login without Passwords
* Plugin URI: https://github.com/chrmrtns/keyless-auth
* Description: Enhanced passwordless authentication allowing users to login securely without passwords via email magic links. Fork of Passwordless Login by Cozmoslabs with additional security features.
* Version: 3.0.0
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
define('CHRMRTNS_KLA_VERSION', '3.0.0');
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
        'meta_key' => 'chrmrtns_kla_login_token',
        'fields' => 'ID'
    ));

    foreach ($users_with_tokens as $user_id) {
        delete_user_meta($user_id, 'chrmrtns_kla_login_token');
        delete_user_meta($user_id, 'chrmrtns_kla_login_token_expiration');
    }
}
MAINFILE

echo "✅ Updated: keyless-auth.php"

echo ""
echo "✅ Refactoring complete!"
echo ""
echo "📋 Summary:"
echo "  - Created 6 new namespaced class files"
echo "  - Created Main.php bootstrap class"
echo "  - Updated main plugin file"
echo ""
echo "⚠️  IMPORTANT: Review the generated files before testing!"
echo ""
echo "🧪 Next steps:"
echo "  1. Review all generated files in includes/"
echo "  2. Check for any syntax errors"
echo "  3. Test plugin activation"
echo "  4. Test magic link login"
echo "  5. Test 2FA functionality"
echo ""
