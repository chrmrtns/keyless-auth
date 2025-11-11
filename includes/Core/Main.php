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
use Chrmrtns\KeylessAuth\Core\WooCommerce;
use Chrmrtns\KeylessAuth\Core\PasswordReset;

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main plugin bootstrap class
 *
 * Orchestrates plugin initialization using dependency injection container.
 * Responsibilities:
 * - Register services in DI container
 * - Bootstrap components in correct order
 * - Manage plugin lifecycle hooks
 * - Provide plugin action links
 *
 * @since 3.0.0
 */
class Main {

    /**
     * Plugin instance
     *
     * @var Main|null
     */
    private static $instance = null;

    /**
     * Dependency injection container
     *
     * @var Container
     */
    private $container;

    /**
     * Get plugin instance (singleton)
     *
     * @return Main Plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Set up hooks
     */
    private function __construct() {
        // Initialize container
        $this->container = new Container();

        // Register services
        $this->register_services();

        // Set up WordPress hooks
        add_action('plugins_loaded', array($this, 'init'));
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_filter('plugin_action_links_' . plugin_basename(CHRMRTNS_KLA_PLUGIN_FILE), array($this, 'add_plugin_action_links'));
    }

    /**
     * Get the DI container
     *
     * @return Container DI container instance
     */
    public function get_container() {
        return $this->container;
    }

    /**
     * Register services in DI container
     *
     * Services are registered as factories (closures) for lazy loading.
     * Each service is only instantiated when first requested.
     *
     * @return void
     */
    private function register_services() {
        // Database service
        $this->container->register('database', function($container) {
            return new Database();
        });

        // Core service (requires database)
        $this->container->register('core', function($container) {
            return new Core();
        });

        // Admin service (only loaded in admin context)
        $this->container->register('admin', function($container) {
            return new Admin();
        });

        // SMTP service
        $this->container->register('smtp', function($container) {
            return new SMTP();
        });

        // Mail logger service
        $this->container->register('mail_logger', function($container) {
            return new MailLogger();
        });

        // 2FA core service (singleton)
        $this->container->register('2fa_core', function($container) {
            return TwoFACore::get_instance();
        });

        // 2FA frontend service
        $this->container->register('2fa_frontend', function($container) {
            return new TwoFAFrontend();
        });

        // WooCommerce integration service (conditional)
        $this->container->register('woocommerce', function($container) {
            return new WooCommerce();
        });

        // Password reset service
        $this->container->register('password_reset', function($container) {
            return new PasswordReset();
        });
    }

    /**
     * Initialize plugin - Bootstrap all services
     *
     * @return void
     */
    public function init() {
        $this->bootstrap_services();
    }

    /**
     * Bootstrap services in correct order
     *
     * Services are initialized from the container, respecting dependencies.
     * Some services are conditionally loaded based on context or settings.
     *
     * @return void
     */
    private function bootstrap_services() {
        // 1. Initialize database first (required by other services)
        global $chrmrtns_kla_database;
        $chrmrtns_kla_database = $this->container->get('database');

        // 2. Initialize core functionality
        $this->container->get('core');

        // 3. Initialize admin (only in admin context)
        if (is_admin()) {
            $this->container->get('admin');
        }

        // 4. Initialize email services
        $this->container->get('smtp');
        $this->container->get('mail_logger');

        // 5. Initialize 2FA services
        global $chrmrtns_kla_2fa_core;
        $chrmrtns_kla_2fa_core = $this->container->get('2fa_core');
        $this->container->get('2fa_frontend');

        // 6. Initialize WooCommerce integration (conditional)
        if (class_exists('WooCommerce') && get_option('chrmrtns_kla_enable_woocommerce', '0') === '1') {
            $this->container->get('woocommerce');
        }

        // 7. Initialize password reset
        $this->container->get('password_reset');
    }

    /**
     * Load plugin textdomain
     *
     * Note: Since WordPress 4.6, plugins hosted on WordPress.org no longer need
     * to manually call load_plugin_textdomain(). WordPress automatically loads
     * translations from wordpress.org's translation system.
     * This method is kept for backwards compatibility but does nothing.
     *
     * @since 1.0.0
     * @deprecated 3.3.0 WordPress.org automatically handles translations
     */
    public function load_textdomain() {
        // WordPress.org automatically loads translations since WP 4.6
        // No action needed for plugins hosted on WordPress.org
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
