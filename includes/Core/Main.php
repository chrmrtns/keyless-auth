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
